/* Project Timber — category page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  (function(){
    var hs=document.getElementById('hsearch'); if(!hs) return;
    var inp=hs.querySelector('input');
    function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
    document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
  })();


  // mobile menu
  (function(){ var m=document.querySelector('.mainhead .menu'), p=document.getElementById('primnav');
    if(m&&p){ m.addEventListener('click',function(){ var o=p.classList.toggle('open'); m.setAttribute('aria-expanded',o); }); } })();
  // support widget
  (function(){ var sup=document.getElementById('support'); if(!sup) return; var launch=sup.querySelector('.launch');
    function toggle(force){ var open=(force!==undefined)?force:!sup.classList.contains('open'); sup.classList.toggle('open',open); if(launch) launch.setAttribute('aria-expanded',open); }
    if(launch) launch.addEventListener('click',function(){ toggle(); });
    document.querySelectorAll('.supporttrigger').forEach(function(b){ b.addEventListener('click',function(){ toggle(true); }); });
    if(launch && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) launch.classList.toggle('show-phone'); }, 3000); } })();

  // ---------------- category grid: live products (progressive enhancement) ----------------
  // Ported from the prototype (scripts-to-migrate/category-page-pt-v3.html). Builds the grid from
  // the WooCommerce REST API for the category the URL points at (?slug= / last path segment / default),
  // via the key-free Timber WC proxy. Composite products report price 0, so each card's "From £X" is
  // the cheapest SIZE-option price resolved from composite_scenarios.
  // DESIGN untouched: emitted cards/filters reuse the page's own classes. Because the proxy sits behind
  // Cloudflare (unreachable off-origin), this is PROGRESSIVE: the page's curated static grid + range
  // filter stay as the fallback, and the live grid only takes over once the fetch succeeds.
  (function(){
    // In the WP theme, functions.php injects window.PT_WC_BASE (current site origin) so the API
    // is same-origin on staging/live; falls back to production for the standalone prototype.
    var DEFAULT_BASE=(typeof window!=='undefined' && window.PT_WC_BASE) ? window.PT_WC_BASE : 'https://www.projecttimber.com';
    var PROXY_ROUTE='/wp-json/timber/v1/wc';       // key-free read-only proxy → wc/v3
    var DEFAULT_SLUG='garden-offices';              // fallback when no slug is in the URL

    var grid=document.getElementById('grid'); if(!grid) return;
    var noRes=document.getElementById('noresults');
    var promo=grid.querySelector('.promo-card');
    var countEl=document.getElementById('count');
    var drawer=document.getElementById('drawer'), backdrop=document.getElementById('backdrop'), sup=document.getElementById('support');

    // --- helpers ---
    function baseUrl(){ return DEFAULT_BASE.replace(/\/+$/,''); }
    function api(path){ var q=path.indexOf('?'); var route=q>-1?path.slice(0,q):path, extra=q>-1?path.slice(q+1):''; var url=baseUrl()+PROXY_ROUTE+'?path='+encodeURIComponent(route); return extra?url+'&'+extra:url; }
    function getJSON(url){
      var ctrl=('AbortController' in window)?new AbortController():null;
      var t=ctrl?setTimeout(function(){ ctrl.abort(); },20000):null;
      return fetch(url,{headers:{Accept:'application/json'}, signal:ctrl?ctrl.signal:undefined}).then(function(r){
        if(t) clearTimeout(t);
        return r.json().then(function(j){ if(!r.ok) throw new Error(j&&j.message?j.message:('HTTP '+r.status)); return j; });
      });
    }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function fmt(n){ return '£'+Math.round(n).toLocaleString('en-GB'); }
    // Campaign display discount for this category page (visual only; real money-off is the
    // auto-applied coupon at checkout). PT_DISCOUNT_PCT is injected by functions.php.
    var DISC=(typeof window!=='undefined' && typeof window.PT_DISCOUNT_PCT==='number' && window.PT_DISCOUNT_PCT>0) ? window.PT_DISCOUNT_PCT : 0;
    function fmtDisc(n){ return DISC>0 ? '<span class="was">'+fmt(n)+'</span><span class="now">'+fmt(n - n*DISC/100)+'</span>' : '<b>'+fmt(n)+'</b>'; }
    function slugify(s){ return String(s||'').toLowerCase().replace(/&amp;/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''); }
    function setText(sel,txt){ var e=document.querySelector(sel); if(e) e.textContent=txt; }
    function sizeVal(name){ var n=(String(name).match(/\d+(?:\.\d+)?/g)||[]).map(Number); var w=n[0]||0,h=n[1]||0; return [w*h,w,h]; }
    function compact(name){ return String(name).replace(/\s*x\s*/i,'×').replace(/\s+/g,''); }
    function attrOpts(p,name){ var a=(p.attributes||[]).filter(function(x){ return String(x.name).toLowerCase()===name; })[0]; return a?(a.options||[]):[]; }

    // Filter facets, in drawer order (empty facets are skipped when built).
    var FACETS=[
      { key:'range',        attr:'Range',     title:'Product range', open:true },
      { key:'size',         attr:'Size',      title:'Size' },
      { key:'treatment',    attr:'Treatment', title:'Treatment' },
      { key:'windows',      attr:'Windows',   title:'Windows' },
      { key:'roof',         attr:'Style',     title:'Roof style' },
      { key:'availability', attr:null,        title:'Availability' }
    ];
    function facetLabel(key,v){ var s=String(v).replace(/&amp;/g,'&'); return key==='size'?s.replace(/\s*x\s*/i,' × '):s; }
    function facetValues(p,f){
      if(f.attr) return attrOpts(p,f.attr.toLowerCase()).map(function(v){ return { slug:slugify(v), label:facetLabel(f.key,v) }; });
      if(f.key==='availability') return (p.stock_status==='instock')?[{slug:'instock',label:'In stock'}]:[];
      return [];
    }
    function facetData(p){ return FACETS.map(function(f){ var slugs=facetValues(p,f).map(function(v){ return v.slug; }); return slugs.length?('data-f-'+f.key+'="'+esc(slugs.join(' '))+'"'):''; }).filter(Boolean).join(' '); }
    function sizesLine(p){ var opts=attrOpts(p,'size').slice().sort(function(a,b){ var A=sizeVal(a),B=sizeVal(b); return A[0]-B[0]||A[1]-B[1]; }); if(!opts.length) return ''; var n=opts.length, lo=compact(opts[0]), hi=compact(opts[n-1]); return n+' size'+(n>1?'s':'')+' · '+(lo===hi?lo:lo+'–'+hi); }

    // size-component option product ids for one composite product (→ for the "from" price)
    function sizeOptionIds(p){
      var sizeCid=null;
      (p.composite_components||[]).forEach(function(c){ if(String(c.title||'').toLowerCase()==='size') sizeCid=String(c.id); });
      var ids={};
      (p.composite_scenarios||[]).forEach(function(s){ (s.configuration||[]).forEach(function(it){ if(String(it.component_id)===sizeCid) (it.component_options||[]).forEach(function(x){ if(+x>0) ids[+x]=1; }); }); });
      return Object.keys(ids).map(Number);
    }
    function fetchPrices(ids){
      ids=ids.filter(function(x){ return x>0; }); if(!ids.length) return Promise.resolve({});
      var out={}, jobs=[];
      for(var i=0;i<ids.length;i+=100){ var chunk=ids.slice(i,i+100); jobs.push(getJSON(api('products?per_page=100&_fields=id,price&include='+chunk.join(','))).then(function(list){ (list||[]).forEach(function(x){ out[x.id]=parseFloat(x.price)||0; }); })); }
      return Promise.all(jobs).then(function(){ return out; });
    }

    // --- card + filter rendering (reuses the page's card/drawer design) ---
    function cardHTML(p,price){
      var imgs=(p.images||[]).map(function(i){ return i.src; });
      var img0=imgs[0]||'', img1=imgs[1]||img0;
      var sizes=sizesLine(p), facets=facetData(p);
      var priceHTML=price>0 ? 'From '+fmtDisc(price) : 'View options';
      // link to the local product-page template; its configurator reads ?product=<id>
      return '<a class="prod" href="projecttimber-product-page.html?product='+encodeURIComponent(p.id)+'" data-price="'+(price||0)+'"'+(facets?' '+facets:'')+'>'+
        '<div class="ph duo">'+
          (img0?'<img class="pimg" src="'+esc(img0)+'" alt="'+esc(p.name)+'">':'')+
          (img1?'<img class="pscene" src="'+esc(img1)+'" alt="" aria-hidden="true">':'')+
        '</div>'+
        '<div class="pbody"><h3>'+esc(p.name)+'</h3><div class="pprice">'+priceHTML+'</div>'+
          (sizes?'<div class="psizes">'+esc(sizes)+'</div>':'')+
        '</div></a>';
    }
    function buildFilters(products){
      var body=document.querySelector('.drawer-body'); if(!body) return;
      body.innerHTML=FACETS.map(function(f){
        var counts={}, labels={};
        products.forEach(function(p){ var seen={}; facetValues(p,f).forEach(function(v){ if(seen[v.slug]) return; seen[v.slug]=1; counts[v.slug]=(counts[v.slug]||0)+1; labels[v.slug]=v.label; }); });
        var keys=Object.keys(counts); if(!keys.length) return '';
        if(f.key==='size') keys.sort(function(a,b){ var A=sizeVal(labels[a]),B=sizeVal(labels[b]); return A[0]-B[0]||A[1]-B[1]; });
        else keys.sort(function(a,b){ return String(labels[a]).localeCompare(String(labels[b])); });
        var opts=keys.map(function(k){ return '<label class="fopt"><input type="checkbox" value="'+esc(k)+'"> '+esc(labels[k])+' <span class="ct">'+counts[k]+'</span></label>'; }).join('');
        return '<details class="fgroup"'+(f.open?' open':'')+'><summary>'+esc(f.title)+'</summary><div class="opts" data-filter="'+esc(f.key)+'">'+opts+'</div></details>';
      }).join('');
    }
    function clearProducts(){ grid.querySelectorAll('.prod').forEach(function(c){ c.remove(); }); }
    // Keep the promo card as the 2nd VISIBLE grid item at all times.
    function placePromo(){
      if(!promo) return;
      var vis=[].slice.call(grid.querySelectorAll('.prod')).filter(function(c){ return !c.hidden; });
      if(vis.length>=2) grid.insertBefore(promo, vis[1]); else grid.insertBefore(promo, noRes);
    }
    // loading skeleton — shown until live products are fetched & mapped (replaces the static cards)
    function showSkeleton(n){
      clearProducts();
      var card='<a class="prod skel" aria-hidden="true"><div class="ph duo"></div>'+
        '<div class="pbody"><h3><span class="sk"></span></h3><div class="pprice"><span class="sk"></span></div><div class="psizes"><span class="sk"></span></div></div></a>';
      var h=''; for(var i=0;i<(n||8);i++) h+=card;
      noRes.insertAdjacentHTML('beforebegin', h); noRes.hidden=true;
      placePromo();
      if(countEl) countEl.textContent='…';
      var foot=document.querySelector('.grid-foot'); if(foot) foot.textContent='Loading products…';
    }
    function showMessage(msg){
      clearProducts(); placePromo(); clearIntro();
      if(noRes){ noRes.hidden=false; noRes.textContent=msg; }
      if(countEl) countEl.textContent='0';
      var foot=document.querySelector('.grid-foot'); if(foot) foot.textContent='';
    }

    // --- counters + filtering ---
    var cards=[], total=grid.querySelectorAll('.prod').length;
    function setCounts(shown){
      if(countEl) countEl.textContent=shown;
      var foot=document.querySelector('.grid-foot');
      if(foot) foot.innerHTML=(shown===total?'Showing all <b>'+total+'</b>':'Showing <b>'+shown+'</b> of '+total)+' product'+(total===1?'':'s');
    }
    // multi-facet (live): match ANY value within a group, ALL active groups (used after live render)
    function applyDynamic(){
      var active=FACETS.map(function(f){ var vals=[].slice.call(document.querySelectorAll('.opts[data-filter="'+f.key+'"] input:checked')).map(function(b){ return b.value; }); return { key:f.key, vals:vals }; }).filter(function(a){ return a.vals.length; });
      var shown=0;
      cards.forEach(function(c){ var ok=active.every(function(a){ var tokens=(c.getAttribute('data-f-'+a.key)||'').split(' ').filter(Boolean); return tokens.some(function(t){ return a.vals.indexOf(t)>-1; }); }); c.hidden=!ok; if(ok) shown++; });
      placePromo(); setCounts(shown); if(noRes) noRes.hidden=shown>0;
    }
    // range-only (static fallback): mirrors the original page, using each card's data-range
    function applyStatic(){
      var active=[].slice.call(document.querySelectorAll('.opts[data-filter="range"] input:checked')).map(function(b){ return b.value; });
      var shown=0;
      cards.forEach(function(c){ var ok=active.length===0 || active.indexOf(c.dataset.range)>-1; c.hidden=!ok; if(ok) shown++; });
      placePromo(); setCounts(shown); if(noRes) noRes.hidden=shown>0;
    }
    var runFilter=applyStatic;   // static until (and unless) live data arrives
    function refreshGrid(){
      cards=[].slice.call(grid.querySelectorAll('.prod'));
      [].slice.call(document.querySelectorAll('.drawer-body .opts input')).forEach(function(b){ b.addEventListener('change',function(){ runFilter(); }); });
    }

    // --- static controls (bound once; read live refs) ---
    function open(){ drawer.classList.add('open'); backdrop.classList.add('open'); document.body.style.overflow='hidden'; if(sup) sup.style.display='none'; }
    function close(){ drawer.classList.remove('open'); backdrop.classList.remove('open'); document.body.style.overflow=''; if(sup) sup.style.display=''; }
    document.getElementById('openFilters').addEventListener('click',open);
    document.getElementById('closeFilters').addEventListener('click',close);
    document.getElementById('applyFilters').addEventListener('click',close);
    backdrop.addEventListener('click',close);
    document.getElementById('resetFilters').addEventListener('click',function(){ document.querySelectorAll('.drawer input[type=checkbox]').forEach(function(b){ b.checked=false; }); runFilter(); });
    document.addEventListener('click',function(e){ var t=e.target.closest&&e.target.closest('#clearFilters'); if(t){ e.preventDefault(); document.querySelectorAll('.drawer input[type=checkbox]').forEach(function(b){ b.checked=false; }); runFilter(); } });
    document.getElementById('sort').addEventListener('change',function(){
      var v=this.value, arr=[].slice.call(grid.querySelectorAll('.prod'));
      if(v==='low') arr.sort(function(a,b){ return a.dataset.price-b.dataset.price; });
      else if(v==='high') arr.sort(function(a,b){ return b.dataset.price-a.dataset.price; });
      arr.forEach(function(c){ grid.insertBefore(c,noRes); });
      placePromo();
    });

    // --- which category? ---
    function categorySlug(){
      // WP theme hands us the exact queried term slug — most reliable, works under any permalink base.
      if(typeof window!=='undefined' && window.PT_CATEGORY_SLUG) return slugify(window.PT_CATEGORY_SLUG);
      try{ var q=new URLSearchParams(location.search); var s=q.get('slug')||q.get('category')||q.get('cat'); if(s) return slugify(s); }catch(e){}
      var segs=location.pathname.split('/').filter(Boolean);
      var last=segs.length?segs[segs.length-1]:'';
      if(last && !/\.html?$/i.test(last)) return slugify(last);
      return DEFAULT_SLUG;
    }
    function paragraphsHTML(text){ return String(text||'').trim().split(/\r?\n\s*\r?\n/).map(function(p){ p=p.trim(); return p?('<p>'+esc(p).replace(/\r?\n/g,'<br>')+'</p>'):''; }).filter(Boolean).join(''); }
    // Provisional title from the slug (e.g. "log-cabins" → "Log Cabins") so the header is right instantly.
    function slugTitle(s){ return String(s||'').replace(/-/g,' ').replace(/\b\w/g,function(c){ return c.toUpperCase(); }).trim() || 'Products'; }
    function setHeader(name){ setText('.cat-intro h1',name); setText('.crumbs .here',name); try{ document.title=name+' — Project Timber'; }catch(e){} }
    // Loading state for the intro copy: shimmer lines in the lede + hide the bottom SEO block.
    function introSkeleton(){
      var p=document.querySelector('.cat-intro p'); if(p){ p.classList.add('cat-skel'); p.innerHTML='<span class="sk"></span><span class="sk"></span><span class="sk"></span>'; }
      var sec=document.querySelector('.cat-bottom'); if(sec) sec.style.display='none';
    }
    function clearIntro(){ var p=document.querySelector('.cat-intro p'); if(p){ p.classList.remove('cat-skel'); p.textContent=''; } var sec=document.querySelector('.cat-bottom'); if(sec) sec.style.display='none'; }
    // Fill the intro lede + bottom SEO block from the live category description (also clears the skeleton).
    function renderDescription(cat){
      var p=document.querySelector('.cat-intro p'), sec=document.querySelector('.cat-bottom');
      var desc=String(cat&&cat.description||'').trim();
      if(p) p.classList.remove('cat-skel');
      if(!desc){ if(p) p.textContent=''; if(sec) sec.style.display='none'; return; }
      var isHTML=/<(p|h[1-6]|ul|ol|div|section|br)\b/i.test(desc);
      if(p){
        if(isHTML){ var d=document.createElement('div'); d.innerHTML=desc; var t=(d.textContent||'').replace(/\s+/g,' ').trim(); p.textContent = t.length>240 ? (t.slice(0,237).replace(/\s+\S*$/,'')+'…') : t; }
        else p.textContent = desc.split(/\r?\n\s*\r?\n/)[0].trim();
      }
      if(sec){ sec.style.display=''; var wrap=sec.querySelector('.wrap'); if(wrap){ var h2=wrap.querySelector('h2'); if(h2) h2.textContent=cat.name; wrap.querySelectorAll('p').forEach(function(x){ x.remove(); }); wrap.insertAdjacentHTML('beforeend', isHTML?desc:paragraphsHTML(desc)); } }
    }
    // session cache → repeat visits within the tab render instantly, with no skeleton flash
    function catKey(slug){ return 'ptCat:'+baseUrl()+':'+slug; }
    function saveCatCache(slug,data){ if(!slug) return; try{ sessionStorage.setItem(catKey(slug), JSON.stringify(data)); }catch(e){} }
    function loadCatCache(slug){ try{ var c=JSON.parse(sessionStorage.getItem(catKey(slug))||'null'); return (c&&c.cat&&c.products)?c:null; }catch(e){ return null; } }
    function render(cat,products,prices){
      if(cat){ setHeader(cat.name); renderDescription(cat); }
      clearProducts();
      noRes.insertAdjacentHTML('beforebegin', products.map(function(p){ return cardHTML(p, prices[p.id]||0); }).join(''));
      buildFilters(products);
      total=products.length;
      runFilter=applyDynamic;      // live cards carry data-f-* → switch to multi-facet filtering
      if(noRes){ if(!products.length){ noRes.hidden=false; noRes.textContent='No products found in this category.'; } else noRes.hidden=true; }
      placePromo(); setCounts(total); refreshGrid();
      if(cat&&cat.slug) saveCatCache(cat.slug,{cat:cat,products:products,prices:prices});
    }

    // Repeat visit within the session → render instantly from cache (NO skeleton flash/overlap).
    // First visit → provisional title from the slug + skeletons for the intro copy and grid.
    var pageSlug=categorySlug();

    // SERVER-RENDERED MODE (WP theme): taxonomy-product_cat.php already emitted the real
    // product cards + filter groups server-side. Skip all fetching — just wire up the
    // multi-facet filtering, counts and sort over the cards already in the DOM.
    if(grid.querySelector('.prod:not(.skel)')){
      total=grid.querySelectorAll('.prod').length;
      runFilter=applyDynamic;      // server cards carry data-f-* → multi-facet filtering
      refreshGrid();               // collect cards[] + bind filter-checkbox listeners
      setCounts(total);
      return;                      // done — no cache/skeleton/fetch
    }

    var cachedCat=loadCatCache(pageSlug);
    if(cachedCat){
      render(cachedCat.cat, cachedCat.products, cachedCat.prices);
    } else {
      setHeader(slugTitle(pageSlug));
      introSkeleton();
      showSkeleton(8);
    }

    // Fast path — single cached mu-plugin endpoint: parent + every direct product with its
    // "from" price + facet attributes resolved server-side, public + CORS (like the config route).
    function catUrl(slug){ return baseUrl()+'/wp-json/wc/v3/category/'+encodeURIComponent(slug)+'/products'; }
    function loadViaEndpoint(slug){
      return getJSON(catUrl(slug)).then(function(res){
        var cat=res&&res.category, products=(res&&res.products)||[];
        if(!cat) throw new Error('category endpoint returned no category');
        var prices={}; products.forEach(function(p){ prices[p.id]=p.price||0; });
        render(cat,products,prices);
      });
    }
    // Fallback — original proxy flow: category lookup + product list, then batch-resolve prices
    // client-side. Kept so nothing regresses if the endpoint is missing/blocked.
    function loadViaProxy(slug){
      return getJSON(api('products/categories?slug='+encodeURIComponent(slug)+'&_fields=id,name,slug,count,description')).then(function(cats){
        var cat=(cats&&cats[0])||null; if(!cat) throw new Error('No category found for “'+slug+'”.');
        var fields='id,name,permalink,price,images,attributes,stock_status,categories,composite_components,composite_scenarios';
        return getJSON(api('products?status=publish&per_page=100&category='+cat.id+'&_fields='+fields)).then(function(products){
          // WC REST's category filter is hierarchical (include_children=true) → keep only DIRECT members.
          products=(products||[]).filter(function(p){ return (p.categories||[]).some(function(c){ return c.id===cat.id; }); });
          var all={}; products.forEach(function(p){ sizeOptionIds(p).forEach(function(id){ all[id]=1; }); });
          return fetchPrices(Object.keys(all).map(Number)).then(function(priceMap){
            var prices={};
            products.forEach(function(p){ var m=Infinity; sizeOptionIds(p).forEach(function(id){ var pr=priceMap[id]; if(pr>0 && pr<m) m=pr; }); prices[p.id]=(m===Infinity)?(parseFloat(p.price)||0):m; });
            render(cat,products,prices);
          });
        });
      });
    }
    // endpoint first; on ANY failure fall back to the proxy flow; only the final failure surfaces.
    (function load(){
      if(cachedCat) return;   // already rendered instantly from the session cache
      loadViaEndpoint(pageSlug).catch(function(e){ if(e&&e.message) console.warn('category endpoint unavailable → proxy flow:', e.message); return loadViaProxy(pageSlug); })
        .catch(function(err){ console.warn('Live category unavailable.', err&&err.message); showMessage('We couldn’t load these products right now — please refresh the page.'); });
    })();
  })();

