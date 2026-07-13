/* Project Timber — cart page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  // ---------- filled / empty state toggle (preview bar kept for the standalone mockup) ----------
  var filled=document.getElementById('filled'), empty=document.getElementById('empty');
  var bF=document.getElementById('bFilled'), bE=document.getElementById('bEmpty');
  function show(state){
    var isF = state==='filled';
    filled.classList.toggle('hidden', !isF); filled.style.display = isF?'flex':'none';
    empty.classList.toggle('hidden', isF); empty.style.display = isF?'none':'flex';
    if(bF) bF.classList.toggle('on', isF); if(bE) bE.classList.toggle('on', !isF);
  }
  if(bF) bF.addEventListener('click',function(){ show('filled'); });
  if(bE) bE.addEventListener('click',function(){ show('empty'); });

  // ---------- live cart via the WooCommerce Store API ----------
  // A cart is per-visitor SESSION state, so it does NOT use the consumer-key proxy that the
  // product/category pages use. The Store API is cookie + nonce based and built for the browser:
  // send the session cookie (credentials:'include') + the Nonce header. This only works when the
  // page is served same-origin on the WordPress site; as a loose file it keeps the static demo.
  (function(){
    var STORE=location.origin+'/wp-json/wc/store/v1';
    var nonce=window.wcStoreApiNonce||'';      // WP localises this when Store API blocks load
    var $=function(s){ return document.querySelector(s); };
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function stripTags(s){ return String(s==null?'':s).replace(/<[^>]*>/g,'').trim(); }
    function setText(sel,t){ var e=$(sel); if(e) e.textContent=t; }
    // WooCommerce returns names/config values HTML-encoded (&times; &ndash; &#8211;…).
    var _dec=document.createElement('textarea');
    function decode(s){ _dec.innerHTML=String(s==null?'':s); return _dec.value; }
    function disp(s){ return esc(stripTags(decode(s))); }   // decode entities → strip tags → re-escape safely
    // Composite config values arrive as "<selection> &times; <qty> &ndash; <price>".
    // Keep just the selection, and prettify dimension "24 x 10" → "24 × 10".
    function cfgValue(v){
      return decode(v).replace(/\s*×\s*\d.*$/,'').replace(/(\d)\s*x\s*(\d)/g,'$1 × $2').trim();
    }

    // Store API returns money as integer minor units + currency metadata.
    function money(minor,cur){
      cur=cur||{};
      var u=(cur.currency_minor_unit==null)?2:cur.currency_minor_unit;
      var p=((parseInt(minor,10)||0)/Math.pow(10,u)).toFixed(u).split('.');
      p[0]=p[0].replace(/\B(?=(\d{3})+(?!\d))/g, cur.currency_thousand_separator||',');
      return (cur.currency_prefix||cur.currency_symbol||'£')+p.join(cur.currency_decimal_separator||'.')+(cur.currency_suffix||'');
    }

    function cartFetch(path,opts){
      opts=opts||{}; opts.credentials='include';
      opts.headers=Object.assign({Accept:'application/json'},opts.headers||{});
      if(opts.json!=null){ opts.headers['Content-Type']='application/json'; opts.body=JSON.stringify(opts.json); delete opts.json; }
      if(nonce) opts.headers['Nonce']=nonce;
      return fetch(STORE+path,opts).then(function(r){
        var n=r.headers.get('Nonce'); if(n) nonce=n;          // keep the freshest nonce
        return r.json().then(function(j){ if(!r.ok) throw new Error(j&&j.message?j.message:('HTTP '+r.status)); return j; });
      });
    }

    var CHEV='<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>';
    var TRASH='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>';

    function itemHTML(it){
      var im=(it.images&&it.images[0]&&(it.images[0].thumbnail||it.images[0].src))||'';
      var data=it.item_data||[];
      var key=function(d){ return d.key||d.name||''; };   // this store uses item_data.key
      var rng=''; data.forEach(function(d){ if(String(key(d)).toLowerCase()==='range') rng=d.value; });
      // composite/variation selections → configuration rows (skip hidden + the Range line)
      var rows=data.filter(function(d){ return !d.hidden && String(key(d)).toLowerCase()!=='range'; }).map(function(d){
        return '<div class="crow"><span class="k">'+disp(key(d))+'</span><span class="v">'+esc(cfgValue(d.value))+'</span></div>';
      }).join('');
      var cfg=rows?('<details class="cfg" open><summary>Your configuration '+CHEV+'</summary><div class="rows">'+rows+'</div></details>'):'';
      return '<div class="line"><div class="item" data-key="'+esc(it.key)+'">'+
          '<div class="thumb">'+(im?'<img src="'+esc(im)+'" alt="'+disp(it.name)+'">':'')+'</div>'+
          '<div class="it">'+(rng?'<div class="rng">'+disp(rng)+'</div>':'')+
            '<h3>'+disp(it.name)+'</h3>'+
            '<div class="pr">'+money(it.totals&&it.totals.line_total,it.totals)+'</div>'+
            '<button class="rm" data-key="'+esc(it.key)+'">'+TRASH+' Remove</button>'+
          '</div></div>'+cfg+'</div>';
    }

    // SomewhereWarm Composite Products / Product Bundles add each configured component to the
    // cart as its OWN child line item, linked to a parent container. We only show the parent
    // (whose item_data already summarises the whole configuration) and hide its children.
    function parentKeyOf(it){
      var ex=it.extensions||{};
      return it.composite_parent || it.bundled_by || it.parent
          || (ex.composite_products && ex.composite_products.composite_parent)
          || (ex.composites && ex.composites.composite_parent)
          || (ex.bundles && ex.bundles.bundled_by)
          || null;
    }
    function renderCart(cart){
      var all=(cart&&cart.items)||[];
      var items=all.filter(function(it){ return !parentKeyOf(it); });   // parents only
      if(!items.length){ show('empty'); return; }
      show('filled');
      var box=document.getElementById('cartItems'); if(box) box.innerHTML=items.map(itemHTML).join('');
      var n=cart.items_count||items.reduce(function(a,b){ return a+(b.quantity||0); },0);
      setText('#cartCount', n+' item'+(n===1?'':'s'));
      var t=cart.totals||{};
      setText('#cartSubtotal', money(t.total_items,t));
      setText('#cartTotal', money(t.total_price,t));
      setText('#cartVat', 'Includes '+money(t.total_tax,t)+' VAT');
      // discount line (only when a coupon actually reduces the total)
      var disc=parseInt(t.total_discount,10)||0, dln=$('#cartDiscountLn');
      if(dln) dln.hidden=!(disc>0);
      if(disc>0) setText('#cartDiscount','−'+money(t.total_discount,t));
      renderCoupons(cart.coupons||[]);
      couponMsg('');
    }

    // ---- coupons ----
    function renderCoupons(list){
      var cl=$('#couponList'); if(!cl) return;
      cl.innerHTML=(list||[]).map(function(c){
        var d=(c.totals&&parseInt(c.totals.total_discount,10))?('−'+money(c.totals.total_discount,c.totals)):'';
        return '<div class="coupon"><span class="code">'+disp(c.code)+'</span>'+(d?'<span class="cd">'+d+'</span>':'')+
          '<button class="cx" type="button" data-coupon="'+esc(c.code)+'" aria-label="Remove coupon">✕</button></div>';
      }).join('');
    }
    // WooCommerce error messages arrive HTML-encoded (e.g. Coupon &quot;X&quot;…); decode so
    // entities render as real characters instead of literal &quot;. textContent keeps it safe.
    function couponMsg(msg,isErr){ var e=$('#couponMsg'); if(!e) return; e.textContent=decode(msg||''); e.hidden=!msg; e.classList.toggle('err',!!isErr); }

    // transient toast — confirms actions that otherwise re-render silently (e.g. removing an item).
    // Self-heals: if #toast isn't in the DOM (e.g. this markup was partially integrated elsewhere),
    // build it on <body> so the confirmation still shows.
    var _toastT;
    function toast(msg){
      var t=$('#toast');
      if(!t){
        t=document.createElement('div'); t.id='toast'; t.className='toast';
        t.setAttribute('role','status'); t.setAttribute('aria-live','polite');
        t.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><span class="msg"></span>';
        document.body.appendChild(t);
      }
      t.querySelector('.msg').textContent=msg;
      // reflow so re-triggering the same toast restarts the fade-in transition
      t.classList.remove('show'); void t.offsetWidth; t.classList.add('show');
      clearTimeout(_toastT); _toastT=setTimeout(function(){ t.classList.remove('show'); },2600);
    }
    function applyCoupon(code){
      code=String(code||'').trim(); if(!code) return;
      var btn=$('#couponApply'); if(btn) btn.disabled=true; couponMsg('');
      cartFetch('/cart/apply-coupon',{method:'POST',json:{code:code}})
        .then(function(cart){ var i=$('#couponInput'); if(i) i.value=''; renderCart(cart); toast('Promo code '+code.toUpperCase()+' applied'); })
        .catch(function(err){ couponMsg((err&&err.message)||'Could not apply that code.',true); })
        .then(function(){ if(btn) btn.disabled=false; });
    }
    function removeCoupon(code){
      cartFetch('/cart/remove-coupon',{method:'POST',json:{code:code}})
        .then(function(cart){ renderCart(cart); toast('Promo code '+String(code).toUpperCase()+' removed'); })
        .catch(function(err){ couponMsg((err&&err.message)||'Could not remove coupon.',true); });
    }

    // Delegated Remove — works for live items (have data-key → DELETE) and the static demo (→ empty).
    var box=document.getElementById('cartItems');
    if(box) box.addEventListener('click',function(e){
      var rm=e.target.closest&&e.target.closest('.rm'); if(!rm) return;
      var key=rm.getAttribute('data-key');
      var line=rm.closest&&rm.closest('.line'); var nameEl=line&&line.querySelector('h3');
      var name=(nameEl&&nameEl.textContent.trim())||'Item';
      if(key){ rm.disabled=true; cartFetch('/cart/items/'+encodeURIComponent(key),{method:'DELETE'})
        .then(function(cart){ renderCart(cart); toast(name+' removed'); })
        .catch(function(err){ console.error(err); rm.disabled=false; couponMsg((err&&err.message)||'Could not remove that item.',true); }); }
      else { show('empty'); toast(name+' removed'); }
    });

    // coupon: apply (button + Enter) and remove (✕ on an applied coupon)
    var capply=document.getElementById('couponApply'), cinput=document.getElementById('couponInput');
    if(capply) capply.addEventListener('click',function(){ applyCoupon(cinput&&cinput.value); });
    if(cinput) cinput.addEventListener('keydown',function(e){ if(e.key==='Enter'){ e.preventDefault(); applyCoupon(cinput.value); } });
    var clist=document.getElementById('couponList');
    if(clist) clist.addEventListener('click',function(e){ var b=e.target.closest&&e.target.closest('.cx'); if(b) removeCoupon(b.getAttribute('data-coupon')); });

    // The HTML ships with a skeleton (no hardcoded data), so nothing real-looking flashes
    // before the fetch resolves. Load the live cart and render it; on failure, show empty.
    show('filled');               // the skeleton is already in #cartItems
    cartFetch('/cart').then(renderCart).catch(function(err){
      console.warn('Live cart unavailable.', err&&err.message);
      show('empty');
    });
  })();
