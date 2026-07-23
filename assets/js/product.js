/* Project Timber — product page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  (function(){
    var hs=document.getElementById('hsearch'); if(!hs) return;
    var inp=hs.querySelector('input');
    function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
    document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
  })();


    (function(){ var v=document.getElementById('faqVideo'); if(!v) return; var b=v.querySelector('.fv-play');
      if(b) b.addEventListener('click',function(){ v.innerHTML='<iframe src="https://www.youtube.com/embed/gOu01FhR6BA?autoplay=1&rel=0&modestbranding=1&controls=0&disablekb=1&fs=0&iv_load_policy=3" title="My Den FAQ" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>'; }); })();


  // scroll reveal
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in-view');io.unobserve(e.target);}});},{threshold:.18});
  document.querySelectorAll('[data-anim]').forEach(function(el){io.observe(el);});

  // tap-to-expand spec tiles
  document.querySelectorAll('button.tile').forEach(function(b){
    b.addEventListener('click',function(){var o=b.classList.toggle('open');b.setAttribute('aria-expanded',o);});
  });

  // count-up numerals
  function up(el){var t=+el.dataset.count,d=1000,s=null,u=el.querySelector('.u'),us=u?u.outerHTML:'';
    function step(ts){if(!s)s=ts;var p=Math.min((ts-s)/d,1);el.innerHTML=Math.round(p*t)+us;if(p<1)requestAnimationFrame(step);}requestAnimationFrame(step);}
  var co=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){up(e.target);co.unobserve(e.target);}});},{threshold:.6});
  document.querySelectorAll('[data-count]').forEach(function(el){co.observe(el);});

  // ---------------- live composite configurator + specifications ----------------
  // Ported from the prototype (scripts-to-migrate/product-page-pt-v3.html). Fetches a WooCommerce
  // Composite Product by ID, builds the option steps from its composite_scenarios (size -> valid
  // options per component), drives the preview gallery + specification table from live data, and
  // generates the native ?add-to-cart=<id>&wccp_component_selection[<cid>]=<optId> cart URL.
  // The DESIGN is untouched: every card / row / spec cell reuses the page's existing classes.
  (function(){
    // --- config: product id comes from ?product= (also product_id / pid), else the baked default ---
    // In the WP theme, functions.php injects window.PT_WC_BASE (current site origin) so the API is
    // same-origin on staging/live; falls back to production for the standalone prototype.
    var DEFAULT_BASE=(typeof window!=='undefined' && window.PT_WC_BASE) ? window.PT_WC_BASE : 'https://www.projecttimber.com';
    var PROXY_ROUTE='/wp-json/timber/v1/wc';   // key-free read-only proxy → wc/v3 (used for specs + gallery + fallback)
    var DEFAULT_PID='9235';
    var USE_CONFIG_ENDPOINT=true;              // single-request mu-plugin endpoint; falls back to the proxy flow
    // WP single-product.php injects window.PT_PRODUCT_ID (the queried product) — most reliable; the
    // ?product= param is the standalone-prototype fallback.
    function urlPid(){ if(typeof window!=='undefined' && window.PT_PRODUCT_ID) return String(window.PT_PRODUCT_ID); try{ var q=new URLSearchParams(location.search); return q.get('product')||q.get('product_id')||q.get('pid')||''; }catch(e){ return ''; } }
    function urlSize(){ try{ var q=new URLSearchParams(location.search); return q.get('size')||q.get('size_id')||q.get('sid')||''; }catch(e){ return ''; } }
    var TITLE_KEY={ 'Size':'size','Wall Thickness':'wall','Floor':'floor','Roof Cover':'roof',
      'Guttering':'guttering','Paint Colour':'paint','Colour Trim':'trim','Base':'base' };

    // Per-step disclaimers / descriptions shown under each configurator step (design:
    // projecttimber-product-page.html .cfg-note). Prefer the component's own description
    // from the API when present; otherwise fall back to this generic copy matched by the
    // component's title keyword. Paint/trim also get the "supplied in tins" colour note.
    var STEP_NOTE={
      size:"Sizes are the external footprint in feet (e.g. 8 × 6). Allow a little clearance around the building for delivery, assembly and future maintenance.",
      door:"Choose your door colour and which side it sits — Left or Right as you look at the building from the garden.",
      window:"Pick your window material and position. Side-opening windows aid airflow; 'both ends' adds light and ventilation front and back. Timber is standard; UPVC is a low-maintenance upgrade. Positions are as you look at the building from the garden.",
      floor:"Comes with an insulated tongue-and-groove floor as standard. Upgrade for a more solid feel and heavier loads.",
      laminate:"An optional laminate finish laid over the floor for a ready-to-use interior — choose a tone, or leave it bare to finish your own way.",
      paint:"Thorndown paint, supplied in tins — buildings aren't pre-painted on delivery, so painting is done on site. Swatch colours shown are indicative approximations.",
      assembly:"Prefer not to self-build? Add our assembly service and our team installs the building for you on delivery. Leave as None to build it yourself using the included instructions."
    };
    function isColourComp(c){ return /paint|trim/i.test((c&&(c.title||c.key))||''); }
    function stepNote(c){
      if(c && c.description) return String(c.description);
      var t=String((c&&(c.title||c.key))||'').toLowerCase();
      if(/paint|trim/.test(t)) return STEP_NOTE.paint;
      if(/window|win\b/.test(t)) return STEP_NOTE.window;
      if(/door/.test(t)) return STEP_NOTE.door;
      if(/laminat/.test(t)) return STEP_NOTE.laminate;
      if(/floor/.test(t)) return STEP_NOTE.floor;
      if(/assembl/.test(t)) return STEP_NOTE.assembly;
      if(/size/.test(t)) return STEP_NOTE.size;
      return '';
    }

    // --- runtime state ---
    var product=null, components=[], sizeCid=null, scenarios={}, meta={}, sel={}, sizeId=null, curPid=null, pendingSize=null;

    // --- el refs (match the page markup) ---
    var $=function(id){ return document.getElementById(id); };
    var elStatus=$('cfgStatus'), elRows=$('cfgRows'), elPrice=$('cfgPrice'), elAdd=$('cfgAdd'),
        elSize=$('cfgSize'), elImg=$('cfgImg'), elName=$('cfgProdName'), elDeliv=$('cfgDeliv');

    function fmt(n){ return '£'+Math.round(n).toLocaleString('en-GB')+'.00'; }
    function fmtm(n){ return '£'+Math.round(n).toLocaleString('en-GB'); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    // --- session cache: makes reloads / repeat product loads instant ---
    function ckey(pid){ return 'ptCfg:'+DEFAULT_BASE+':'+pid; }
    function saveCache(pid){
      if(!pid||!product) return;
      try{ sessionStorage.setItem(ckey(pid), JSON.stringify({ product:product, components:components, scenarios:scenarios, sizeCid:sizeCid, meta:meta })); }catch(e){}
    }
    function loadCache(pid){ try{ var c=JSON.parse(sessionStorage.getItem(ckey(pid))||'null'); return (c&&c.product)?c:null; }catch(e){ return null; } }

    // --- networking ---
    function baseUrl(){ return DEFAULT_BASE.replace(/\/+$/,''); }
    // proxy: GET <base>/wp-json/timber/v1/wc?path=<wc/v3 path>[&<extra query>] — keys are added server-side.
    function api(path){
      var q=path.indexOf('?'); var route=q>-1?path.slice(0,q):path; var extra=q>-1?path.slice(q+1):'';
      var url=baseUrl()+PROXY_ROUTE+'?path='+encodeURIComponent(route);
      return extra?url+'&'+extra:url;
    }
    function cfgUrl(pid){ return baseUrl()+'/wp-json/wc/v3/product/'+encodeURIComponent(pid)+'/config'; }
    // Store API (key-free, same-origin, always present — core WooCommerce Blocks). Used for
    // the product image gallery so it doesn't depend on the prod-only timber/v1/wc proxy,
    // which isn't installed on staging (that call was 404ing).
    function storeUrl(path){ return baseUrl()+'/wp-json/wc/store/v1/'+path; }
    function getJSON(url){
      var ctrl=('AbortController' in window)?new AbortController():null;
      var t=ctrl?setTimeout(function(){ ctrl.abort(); },20000):null;
      return fetch(url,{headers:{Accept:'application/json'}, signal:ctrl?ctrl.signal:undefined}).then(function(r){
        if(t) clearTimeout(t);
        return r.json().then(function(j){ if(!r.ok) throw new Error(j&&j.message?j.message:('HTTP '+r.status)); return j; });
      }, function(err){
        if(t) clearTimeout(t);
        throw new Error(err&&err.name==='AbortError'?'Request timed out (20s) — site slow or unreachable.':((err&&err.message)||'Network error'));
      });
    }
    function status(msg,isErr,busy){ if(!elStatus) return; elStatus.className='cfg-status'+(isErr?' err':''); elStatus.innerHTML=(busy?'<span class="spin"></span>':'')+esc(msg); }

    // ====================== preview gallery (design preserved; images now live) ======================
    var galPrev=document.querySelector('.cfg-navbtn.prev'), galNext=document.querySelector('.cfg-navbtn.next');
    var galDots=$('cfgDots'), galWrap=$('cfgGallery');
    var galleryBase=[];   // parent product gallery images (fetched via proxy, best-effort)
    var gal=[], gi=0;
    function show(i){ if(!gal.length)return; gi=(i+gal.length)%gal.length; if(elImg) elImg.src=gal[gi]; if(galDots){ var d=galDots.children; for(var k=0;k<d.length;k++){ d[k].className='cfg-dot'+(k===gi?' on':''); } } }
    function setGallery(imgs){
      gal=(imgs||[]).filter(Boolean); gi=0;
      if(!gal.length){ if(galPrev)galPrev.style.display='none'; if(galNext)galNext.style.display='none'; if(galDots)galDots.style.display='none'; return; }
      if(galDots){ galDots.innerHTML=''; gal.forEach(function(_,i){ var b=document.createElement('button'); b.type='button'; b.className='cfg-dot'+(i===0?' on':''); b.setAttribute('aria-label','Image '+(i+1)); b.addEventListener('click',function(){ show(i); }); galDots.appendChild(b); }); }
      var multi=gal.length>1;
      if(galPrev) galPrev.style.display=multi?'':'none';
      if(galNext) galNext.style.display=multi?'':'none';
      if(galDots) galDots.style.display=multi?'':'none';
      show(0);
    }
    // Build the slide list for a given lead image: lead first, then the parent gallery (deduped).
    function galleryFor(lead){
      var out=[]; var seen={};
      [lead].concat(galleryBase).forEach(function(src){ if(src && !seen[src]){ seen[src]=1; out.push(src); } });
      return out;
    }
    if(galPrev) galPrev.addEventListener('click',function(){ show(gi-1); });
    if(galNext) galNext.addEventListener('click',function(){ show(gi+1); });
    if(galWrap){ var sx=null; galWrap.addEventListener('touchstart',function(e){ sx=e.touches[0].clientX; },{passive:true}); galWrap.addEventListener('touchend',function(e){ if(sx===null)return; var dx=e.changedTouches[0].clientX-sx; if(Math.abs(dx)>40) show(dx<0?gi+1:gi-1); sx=null; }); }
    // Fetch the parent product's image gallery (best-effort; degrades to the single config image).
    function loadGallery(pid){
      galleryBase=(product&&product.images)?product.images.map(function(im){ return im&&im.src; }).filter(Boolean):[];
      if(galleryBase.length>1){ setGallery(galleryFor((product.images[0]||{}).src)); return; }
      getJSON(storeUrl('products/'+pid)).then(function(p){
        var arr=Array.isArray(p)?p[0]:p;
        galleryBase=(arr&&arr.images)?arr.images.map(function(im){ return im&&(im.src||im.thumbnail); }).filter(Boolean):galleryBase;
        var lead=(sizeId!=null&&meta[sizeId]&&meta[sizeId].img)||galleryBase[0]||(product&&product.images&&product.images[0]&&product.images[0].src);
        setGallery(galleryFor(lead));
      }).catch(function(){ /* keep whatever single image is showing */ });
    }

    // ---- gallery lightbox (click the preview to open · browse · zoom) — shares gal/gi/show ----
    (function initGlb(){
      var glb=document.getElementById('cfgLightbox'); if(!glb || !elImg) return;
      var img=glb.querySelector('.glb-img');
      var prev=glb.querySelector('.glb-prev'), next=glb.querySelector('.glb-next'), count=glb.querySelector('.glb-count');
      function upd(){ if(!gal.length) return; img.classList.remove('zoom'); img.style.transformOrigin='center center'; img.src=gal[gi];
        var multi=gal.length>1; prev.style.display=multi?'':'none'; next.style.display=multi?'':'none';
        if(count){ count.textContent=(gi+1)+' / '+gal.length; count.style.display=multi?'':'none'; } }
      function openLB(){ if(!gal.length) return; upd(); glb.classList.add('open'); glb.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
      function closeLB(){ glb.classList.remove('open'); glb.setAttribute('aria-hidden','true'); document.body.style.overflow=''; img.classList.remove('zoom'); }
      elImg.style.cursor='zoom-in';
      elImg.addEventListener('click', openLB);
      prev.addEventListener('click', function(e){ e.stopPropagation(); show(gi-1); upd(); });
      next.addEventListener('click', function(e){ e.stopPropagation(); show(gi+1); upd(); });
      glb.querySelector('.glb-x').addEventListener('click', closeLB);
      glb.querySelector('.glb-back').addEventListener('click', closeLB);
      img.addEventListener('click', function(e){ e.stopPropagation(); var z=img.classList.toggle('zoom'); if(!z) img.style.transformOrigin='center center'; });
      img.addEventListener('mousemove', function(e){ if(!img.classList.contains('zoom')) return; var r=img.getBoundingClientRect(); img.style.transformOrigin=((e.clientX-r.left)/r.width*100)+'% '+((e.clientY-r.top)/r.height*100)+'%'; });
      document.addEventListener('keydown', function(e){ if(!glb.classList.contains('open')) return; if(e.key==='Escape') closeLB(); else if(e.key==='ArrowLeft'){ show(gi-1); upd(); } else if(e.key==='ArrowRight'){ show(gi+1); upd(); } });
      var sx=null; glb.addEventListener('touchstart',function(e){ sx=e.touches[0].clientX; },{passive:true});
      glb.addEventListener('touchend',function(e){ if(sx===null||img.classList.contains('zoom')){ sx=null; return; } var dx=e.changedTouches[0].clientX-sx; if(Math.abs(dx)>40){ show(dx<0?gi+1:gi-1); upd(); } sx=null; });
    })();

    // ---- earliest delivery date in the summary (auto-computed so it never goes stale) ----
    (function(){
      var el=document.getElementById('delivFrom'); if(!el) return;
      var LEAD_DAYS=42;
      var d=new Date(); d.setDate(d.getDate()+LEAD_DAYS);
      var mo=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var wd=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      function ord(n){ var s=['th','st','nd','rd'], v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); }
      el.textContent=mo[d.getMonth()]+', '+ord(d.getDate())+' '+wd[d.getDay()];
    })();

    // ====================== specifications (design preserved; data now live) ======================
    // The 7 curated spec cards stay exactly as designed; cells with data-spec are filled from
    // GET /products/{sizeId}/specs. data-dim cells hold centimetres and convert for Imperial.
    var CM2IN=0.3937007874, specUnit='metric', specCache={}, curSpecs=null;
    var specImg=$('specImg'), specSeg=$('sizeseg'), unitSeg=$('unitseg');
    var specCells=document.querySelectorAll('#specs [data-spec]');
    var specFallback={}; specCells.forEach(function(c){ specFallback[c.getAttribute('data-spec')]=c.textContent; });
    function specVal(raw,isDim){
      if(raw==null||raw==='') return null;
      var s=String(raw).trim();
      if(!isDim || !/\d/.test(s)) return s;
      var out=s.replace(/\d+(?:\.\d+)?/g,function(m){ return specUnit==='imperial'?(parseFloat(m)*CM2IN).toFixed(1):m; });
      out=out.replace(/\s*x\s*/gi,' × ');
      return out+(specUnit==='imperial'?' in':' cm');
    }
    function renderSpecs(){
      var map={}; if(curSpecs){ ['dimensions','materials','features'].forEach(function(g){ (curSpecs[g]||[]).forEach(function(it){ map[it.key]=it.value; }); }); }
      specCells.forEach(function(cell){
        var key=cell.getAttribute('data-spec'), isDim=cell.hasAttribute('data-dim');
        if(map.hasOwnProperty(key)){ var v=specVal(map[key],isDim); cell.textContent=(v==null)?'—':v; }
        else if(isDim){ var f=specVal(specFallback[key],isDim); if(f!=null) cell.textContent=f; }   // convert authored fallback too
      });
    }
    // Per-size specs come from the public wc/v3/products/{id}/specs route DIRECTLY (returns 200 without
    // keys, same as the config route) — the timber/v1/wc proxy sits behind Cloudflare and 403s off-origin.
    function specUrl(pid){ return baseUrl()+'/wp-json/wc/v3/products/'+encodeURIComponent(pid)+'/specs'; }
    function fetchSpecs(pid){
      if(specCache[pid]) return Promise.resolve(specCache[pid]);
      return getJSON(specUrl(pid)).then(function(res){ var row=Array.isArray(res)?res[0]:res; var specs=(row&&row.specifications)||{}; specCache[pid]=specs; return specs; });
    }
    function loadSpecs(pid){ if(pid==null) return; fetchSpecs(pid).then(function(s){ curSpecs=s; renderSpecs(); }).catch(function(e){ console.error('specs:',e); }); }
    if(unitSeg){ unitSeg.querySelectorAll('button').forEach(function(b){
      b.addEventListener('click',function(){ unitSeg.querySelectorAll('button').forEach(function(x){ x.classList.remove('on'); }); b.classList.add('on'); specUnit=(b.dataset.unit==='imperial')?'imperial':'metric'; renderSpecs(); });
    }); }
    // Size selector under the spec table is rendered live and kept in sync with the configurator.
    function renderSpecSeg(){
      if(!specSeg) return;
      var sizes=sortedSizes();
      specSeg.innerHTML=sizes.map(function(s){ return '<button data-size-id="'+s.id+'"'+(s.id===sizeId?' class="on"':'')+'>'+esc(sizeDisplay(s.name))+'</button>'; }).join('');
    }
    function markSpecSeg(id){ if(!specSeg) return; specSeg.querySelectorAll('button').forEach(function(b){ b.classList.toggle('on', +b.dataset.sizeId===+id); }); }
    if(specSeg){ specSeg.addEventListener('click',function(e){ var b=e.target.closest('button'); if(!b) return; selectSize(+b.dataset.sizeId); }); }

    // ====================== parsing ======================
    function parseConfig(pid,cfg){
      // Prefer the full gallery the /config endpoint now returns (v1.1.0 `images`);
      // fall back to the single `img`. loadGallery() still hits the Store API only if
      // this leaves us with ≤1 image (older mu-plugin / single-image products).
      var cfgImgs=(cfg.images&&cfg.images.length)?cfg.images.map(function(s){ return {src:s}; }):(cfg.img?[{src:cfg.img}]:[]);
      product={ id:cfg.id, name:cfg.name, permalink:cfg.permalink, images:cfgImgs };
      components=(cfg.components||[]).map(function(c){ var key=c.key||TITLE_KEY[c.title]||String(c.title||'').toLowerCase().replace(/\s+/g,'_'); return { id:String(c.id), title:c.title, key:key, optional:!!c.optional, description:c.description||c.desc||'' }; });
      sizeCid=(cfg.sizeCid!=null)?String(cfg.sizeCid):null;
      scenarios={}; meta={}; sel={}; sizeId=null;
      (cfg.sizes||[]).forEach(function(sz){
        meta[sz.id]={ id:sz.id, name:sz.name, price:sz.price||0, img:sz.img||'' };
        var config={}, opts=sz.options||{};
        Object.keys(opts).forEach(function(cid){ var ids=[]; (opts[cid]||[]).forEach(function(o){ ids.push(o.id); if(!meta[o.id]) meta[o.id]={ id:o.id, name:o.name, price:o.price||0, img:o.img||'' }; }); config[cid]=ids; });
        if(sizeCid && !config[sizeCid]) config[sizeCid]=[sz.id];
        scenarios[sz.id]={ name:sz.name, config:config };
      });
    }
    function parseProduct(p){
      product=p; components=[]; scenarios={}; meta={}; sel={}; sizeId=null;
      p.composite_components.forEach(function(c){ var key=TITLE_KEY[c.title]||String(c.title||'').toLowerCase().replace(/\s+/g,'_'); components.push({ id:String(c.id), title:c.title, key:key, optional:!!c.optional, description:c.description||c.desc||'' }); if(key==='size') sizeCid=String(c.id); });
      (p.composite_scenarios||[]).forEach(function(s){
        var cfg={}, sid=null;
        (s.configuration||[]).forEach(function(it){ var cid=String(it.component_id); var ids=(it.component_options||[]).map(Number).filter(function(x){ return x>0; }); cfg[cid]=ids; if(cid===sizeCid && ids.length) sid=ids[0]; });
        if(sid!=null) scenarios[sid]={ name:s.name, config:cfg };
      });
    }

    // --- batch fetch (fallback proxy flow): products by id list → fills meta{} ---
    function fetchProducts(ids){
      ids=ids.filter(function(id){ return id>0 && !meta[id]; });
      if(!ids.length) return Promise.resolve();
      var jobs=[];
      for(var i=0;i<ids.length;i+=100){
        var chunk=ids.slice(i,i+100);
        jobs.push(getJSON(api('products?per_page=100&_fields=id,name,price,images&include='+chunk.join(','))).then(function(list){ list.forEach(function(p){ meta[p.id]={ id:p.id, name:p.name, price:parseFloat(p.price)||0, img:(p.images&&p.images[0]?p.images[0].src:'') }; }); }));
      }
      return Promise.all(jobs);
    }
    function prefetchAll(){
      var ids={};
      Object.keys(scenarios).forEach(function(sid){ ids[sid]=1; var cfg=scenarios[sid].config||{}; for(var cid in cfg) (cfg[cid]||[]).forEach(function(x){ if(x>0) ids[x]=1; }); });
      return fetchProducts(Object.keys(ids).map(Number));
    }

    // ====================== rendering (reuses the page's card/row design) ======================
    // Size option names from the live store carry finish/door text
    // (e.g. "20 x 8 - UPVC - Graphite"); the configurator shows ONLY the dimension.
    function sizeDisplay(name){ var m=String(name==null?'':name).match(/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)/i); return m ? (m[1]+' × '+m[2]) : String(name==null?'':name); }
    // ---- bestseller sizes + size filter (Bestsellers / by depth) — data from window.PT_BEST_SIZES ----
    function normSize(s){ var t=String(s==null?'':s).toLowerCase().replace(/[×]/g,'x'); var m=t.match(/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)/); return m ? (m[1]+'x'+m[2]) : t.replace(/\s+/g,''); }
    var BEST_SIZES=((typeof window!=='undefined'&&window.PT_BEST_SIZES)||[]).map(normSize);
    function isBestSize(name){ return BEST_SIZES.length>0 && BEST_SIZES.indexOf(normSize(name))>-1; }
    // Build the "Bestsellers" + by-depth filter pills above the size grid (mirrors the design).
    function initSizeFilter(){
      if(!elRows) return;
      var grid=elRows.querySelector('.opt-cards[data-mode="size"]'); if(!grid) return;
      if(grid.parentNode.querySelector('.size-groups')) return;
      var cards=[].slice.call(grid.querySelectorAll('.opt-card'));
      function depthOf(c){ var v=(c.dataset.val||'').split(/[×x]/i); return v.length>1 ? v[1].trim() : (c.dataset.val||'').trim(); }
      var depths=[]; cards.forEach(function(c){ var d=depthOf(c); if(d && depths.indexOf(d)<0) depths.push(d); });
      var bests=cards.filter(function(c){ return c.dataset.best; });
      if(depths.length<2 && !bests.length) return;
      depths.sort(function(a,b){ return parseFloat(a)-parseFloat(b); });
      var wrap=document.createElement('div'); wrap.className='size-groups'; wrap.setAttribute('role','tablist'); wrap.setAttribute('aria-label','Filter sizes');
      grid.parentNode.insertBefore(wrap, grid);
      function match(c,key){ return key==='__best' ? !!c.dataset.best : depthOf(c)===key; }
      function showKey(key){
        [].forEach.call(wrap.children,function(b){ var on=b.dataset.key===key; b.classList.toggle('on',on); b.setAttribute('aria-selected', on?'true':'false'); });
        cards.forEach(function(c){ c.classList.toggle('hide', !match(c,key)); });
      }
      function addPill(key,label){ var b=document.createElement('button'); b.type='button'; b.setAttribute('role','tab'); b.dataset.key=key; b.textContent=label; b.addEventListener('click',function(){ showKey(key); }); wrap.appendChild(b); }
      if(bests.length) addPill('__best','Bestsellers');
      depths.forEach(function(d){ addPill(d, d+'ft deep'); });
      var selCard=grid.querySelector('.opt-card.sel');
      showKey(bests.length ? '__best' : (selCard ? depthOf(selCard) : depths[0]));
    }
    function cardHTML(group,opt,selected,colour){
      var label=(group===sizeCid)?sizeDisplay(opt.name):opt.name;
      var isNone=/^\s*none\s*$/i.test(opt.name||'');
      // paint/trim colour swatches carry a "supplied in tins" note; the None card warns to paint within 4 weeks.
      var tins=(colour&&!isNone)?'<span class="tins">*SUPPLIED IN TINS</span>':'';
      var img=opt.img?'<div class="im"><img src="'+esc(opt.img)+'" alt="'+esc(label)+'">'+tins+'</div>':'<div class="im ph">'+tins+'</div>';
      var badge4w=(colour&&isNone)?'<span class="badge4w">⚠ Paint within 4 weeks!*</span>':'';
      var price=(opt.price==null)?'<span class="pr-sk skel-box"></span>':fmt(opt.price);
      var sizeAttrs=(group===sizeCid) ? ' data-val="'+esc(label)+'"'+(isBestSize(opt.name)?' data-best="1"':'') : '';
      return '<div class="opt-card'+(selected?' sel':'')+'" data-group="'+esc(group)+'" data-opt="'+opt.id+'"'+sizeAttrs+'>'+img+badge4w+
        '<div class="nm">'+esc(label)+'</div><div class="pr">'+price+'</div>'+
        '<div class="selbtn">'+(selected?'Selected':'Select')+'</div></div>';
    }
    function rowHTML(idx,label,selId,group,mode,cardsHTML,note){
      var noteHTML=note?'<p class="cfg-note">'+esc(note)+'</p>':'';
      return '<div class="cfg-row'+(idx===1?' open':'')+'">'+
        '<div class="cfg-head"><span class="ix">'+idx+'.</span><span class="lab">'+esc(label)+'</span>'+
        '<span class="sel" id="'+selId+'">—</span><span class="chev">▾</span></div>'+
        '<div class="cfg-body"><div><div class="cfg-inner">'+noteHTML+
        '<div class="opt-cards" data-group="'+esc(group)+'"'+(mode?' data-mode="'+mode+'"':'')+'>'+cardsHTML+'</div>'+
        '</div></div></div></div>';
    }
    function skelCards(n){ var c='<div class="opt-card skel"><div class="im ph"></div><div class="nm"><span class="nm-sk skel-box"></span></div><div class="pr"><span class="pr-sk skel-box"></span></div><div class="selbtn">Select</div></div>'; var h=''; for(var i=0;i<n;i++) h+=c; return h; }
    function skelRow(idx,open){ return '<div class="cfg-row'+(open?' open':'')+'"><div class="cfg-head"><span class="ix">'+idx+'.</span><span class="lab-sk skel-box"></span><span class="sel"></span><span class="chev">▾</span></div><div class="cfg-body"><div><div class="cfg-inner"><div class="opt-cards">'+(open?skelCards(4):'')+'</div></div></div></div></div>'; }
    function showSkeleton(){ if(elRows) elRows.innerHTML=skelRow(1,true); }
    function showOptionSkeletons(){ if(!elRows) return; var all=elRows.querySelectorAll('.cfg-row'); for(var i=all.length-1;i>=1;i--) all[i].remove(); var h=''; for(var k=2;k<=6;k++) h+=skelRow(k,false); elRows.insertAdjacentHTML('beforeend',h); }

    function sizeSortVal(name){ var n=(String(name).match(/\d+(?:\.\d+)?/g)||[]).map(Number); var w=n[0]||0, h=n[1]||0; return [w*h, w, h]; }
    function sortedSizes(){
      var parentImg=(product&&product.images&&product.images[0]&&product.images[0].src)||'';
      var sizes=Object.keys(scenarios).map(function(id){ var m=meta[id]; return { id:+id, name:scenarios[id].name||((m&&m.name)||('#'+id)), price:m?m.price:null, img:(m&&m.img)||parentImg }; });
      sizes.sort(function(a,b){ var A=sizeSortVal(a.name),B=sizeSortVal(b.name); return A[0]-B[0]||A[1]-B[1]||A[2]-B[2]; });
      return sizes;
    }
    function renderSizeRow(){
      var sizes=sortedSizes();
      var cards=sizes.map(function(o){ return cardHTML(sizeCid,o,o.id===sizeId); }).join('');
      var sizeComp=components.filter(function(c){ return c.id===sizeCid; })[0];
      if(elRows) elRows.innerHTML=rowHTML(1,'Size','sel-size',sizeCid,'size',cards,stepNote(sizeComp||{key:'size'}));
      initSizeFilter();
      renderSpecSeg();
    }

    function maybePreselect(){
      if(!pendingSize) return null;
      var want=pendingSize; pendingSize=null;
      var id=scenarios[want]?+want:null;
      if(id==null){ var norm=function(s){ return String(s).toLowerCase().replace(/\s+/g,''); }; Object.keys(scenarios).forEach(function(k){ if(id==null && norm(scenarios[k].name)===norm(want)) id=+k; }); }
      return id!=null ? selectSize(id) : null;
    }

    function selectSize(id){
      sizeId=id; sel={}; sel[sizeCid]=id;
      var sc=scenarios[id]; if(!sc) return Promise.resolve();
      var sm=meta[id];
      loadSpecs(id); markSpecSeg(id);
      if(specImg && sm && sm.img){ specImg.src=sm.img; specImg.alt=(product?product.name:'Product')+' '+sc.name+' preview'; }
      if(elSize) elSize.textContent=sizeDisplay(sc.name);
      setGallery(galleryFor((sm&&sm.img) || (product&&product.images&&product.images[0]&&product.images[0].src)));
      markSelected(sizeCid,id); setSelLabel('sel-size',sizeDisplay(sc.name));
      var need=[]; components.forEach(function(c){ if(c.id===sizeCid) return; (sc.config[c.id]||[]).forEach(function(x){ need.push(x); }); });
      var haveAll=need.every(function(x){ return meta[x]; });
      if(haveAll){ renderOptionRows(sc); status('Configured for '+sc.name+'.'); return Promise.resolve(); }
      status('Loading options for '+sc.name+'…',false,true); showOptionSkeletons();
      return fetchProducts(need).then(function(){ renderOptionRows(sc); status('Configured for '+sc.name+'.'); saveCache(curPid); }).catch(function(err){ console.error(err); status(err.message||'Failed to load options.',true); });
    }

    function renderOptionRows(sc){
      if(!elRows) return;
      var rows=elRows.querySelectorAll('.cfg-row'); for(var i=rows.length-1;i>=1;i--) rows[i].remove();
      var idx=1, html='';
      components.forEach(function(c){
        if(c.id===sizeCid) return;
        var ids=(sc.config[c.id]||[]); if(!ids.length) return;
        idx++;
        var opts=ids.map(function(oid){ return meta[oid]||{id:oid,name:'#'+oid,price:0,img:''}; });
        opts.sort(function(a,b){ return (a.price||0)-(b.price||0); });
        var first=opts[0]; sel[c.id]=first.id;
        var colour=isColourComp(c);
        var cards=opts.map(function(o,n){ return cardHTML(c.id,o,n===0,colour); }).join('');
        html+=rowHTML(idx,c.title,'sel-'+c.key,c.id,'',cards,stepNote(c));
      });
      elRows.insertAdjacentHTML('beforeend',html);
      components.forEach(function(c){ if(c.id===sizeCid) return; if(sel[c.id]!=null){ var m=meta[sel[c.id]]; setSelLabel('sel-'+c.key, m?m.name:('#'+sel[c.id])); } });
      recalc();
    }

    function markSelected(group,optId){
      if(!elRows) return;
      var box=elRows.querySelector('.opt-cards[data-group="'+group+'"]'); if(!box) return;
      box.querySelectorAll('.opt-card').forEach(function(x){ var on=(+x.dataset.opt===+optId); x.classList.toggle('sel',on); var b=x.querySelector('.selbtn'); if(b)b.textContent=on?'Selected':'Select'; });
    }
    function setSelLabel(id,txt){ var e=$(id); if(e) e.textContent=txt; }

    function total(){ var t=0; for(var cid in sel){ var m=meta[sel[cid]]; if(m) t+=m.price; } return t; }
    function siteOrigin(){ if(product && product.permalink){ try{ return new URL(product.permalink).origin; }catch(e){} } return baseUrl(); }
    function cartUrl(){
      if(!product||sizeId==null) return '';
      var parts=['add-to-cart='+product.id];
      for(var cid in sel){ parts.push('wccp_component_selection['+cid+']='+sel[cid]); parts.push('wccp_component_quantity['+cid+']=1'); }
      return siteOrigin()+'/checkout/?'+parts.join('&');
    }
    function recalc(){
      var t=total();
      var payBtn=document.querySelector('.cfg-summary .ptoggle .on'); var pay=payBtn?payBtn.dataset.pay:'cash';
      if(elPrice) elPrice.innerHTML = pay==='finance' ? fmtm(t/120)+' <small>/mo over 120 months*</small>' : fmt(t);
      var bb=document.querySelector('.buybar .p'); if(bb) bb.innerHTML=fmt(t)+' <small>FREE DELIVERY*</small>';
      if(elAdd) elAdd.disabled=(sizeId==null);
      if(elDeliv && sizeId!=null) elDeliv.textContent='Ready to add · '+(scenarios[sizeId]?scenarios[sizeId].name:'');
    }

    // ====================== load ======================
    function afterParse(pid,cached){
      if(elName) elName.textContent=(product&&product.name)||('Product '+pid);
      if(elImg && product&&product.images&&product.images[0]&&product.images[0].src) elImg.src=product.images[0].src;
      setGallery(galleryFor(product&&product.images&&product.images[0]&&product.images[0].src));
      loadGallery(pid);
      renderSizeRow();
      status((cached?'Ready (cached) · ':'')+Object.keys(scenarios).length+' sizes. Choose a size.');
      return maybePreselect();
    }
    function loadViaConfig(pid){
      if(!USE_CONFIG_ENDPOINT) return Promise.reject(new Error('disabled'));
      return getJSON(cfgUrl(pid)).then(function(cfg){
        if(Array.isArray(cfg)) cfg=cfg[0];
        if(!cfg || !cfg.sizes || !cfg.sizes.length) throw new Error('config endpoint returned no sizes');
        parseConfig(pid,cfg); saveCache(pid); afterParse(pid,false);
      });
    }
    function loadViaProxy(pid){
      return getJSON(api('products/'+pid)).then(function(p){
        if(p.type!=='composite' || !p.composite_components || !p.composite_components.length) throw new Error('Product '+pid+' is not a composite product.');
        parseProduct(p); saveCache(pid);
        var pre=afterParse(pid,false);
        var warm=function(){ prefetchAll().then(function(){ if(sizeId==null) renderSizeRow(); saveCache(pid); }).catch(function(){}); };
        if(pre&&pre.then){ pre.then(warm); } else { warm(); }
      });
    }
    function load(){
      var pid=parseInt(urlPid()||DEFAULT_PID,10);
      if(!pid){ status('No product configured.',true); return; }
      curPid=pid; pendingSize=urlSize();
      var cached=loadCache(pid);
      if(cached){ product=cached.product; components=cached.components; scenarios=cached.scenarios; sizeCid=cached.sizeCid; meta=cached.meta||{}; sel={}; sizeId=null; afterParse(pid,true); return; }
      status('Loading…',false,true); if(elAdd) elAdd.disabled=true; showSkeleton();
      loadViaConfig(pid).catch(function(e){ if(e&&e.message) console.warn('config endpoint unavailable → proxy flow:', e.message); return loadViaProxy(pid); })
        .catch(function(err){ console.error(err); status(err.message||'Failed to load. Check the product / connection.',true); });
    }

    // ====================== events ======================
    // Single-open accordion helpers: selecting an option collapses that step (its
    // chosen value stays visible in the header) and opens the NEXT step to choose.
    function cfgRowsList(){ return elRows ? [].slice.call(elRows.querySelectorAll('.cfg-row')) : []; }
    function cfgOpenOnly(row){ cfgRowsList().forEach(function(r){ r.classList.toggle('open', r===row); }); }
    function cfgAdvance(row){
      // Collapse this step / open the next, INSTANTLY (no slide), and pin the step you
      // clicked exactly where it was — so the reflow can't make the browser shift the
      // viewport (scroll-anchoring, or clamping when near the page bottom).
      var head=row?row.querySelector('.cfg-head'):null;
      var before=head?head.getBoundingClientRect().top:null;
      if(elRows) elRows.classList.add('cfg-advancing');
      var rows=cfgRowsList(), next=rows[rows.indexOf(row)+1];
      if(next){ cfgOpenOnly(next); }
      else if(row){ row.classList.remove('open'); }    // last step → collapse; summary/add is ready
      if(head && before!=null){
        var d=head.getBoundingClientRect().top-before;   // any shift the reflow caused
        if(d){ try{ window.scrollBy({ top:d, left:0, behavior:'instant' }); }catch(e){ window.scrollBy(0,d); } }
      }
      if(elRows){ requestAnimationFrame(function(){ if(elRows) elRows.classList.remove('cfg-advancing'); }); }
    }

    // delegated clicks: accordion headers + option cards (rows are dynamic)
    if(elRows) elRows.addEventListener('click',function(e){
      // header click → open just this step (close the others), or collapse it if already open
      var head=e.target.closest('.cfg-head'); if(head){ var hrow=head.closest('.cfg-row'); if(hrow.classList.contains('open')) hrow.classList.remove('open'); else cfgOpenOnly(hrow); return; }
      var card=e.target.closest('.opt-card'); if(!card) return;
      var group=card.dataset.group, optId=+card.dataset.opt;
      // size re-renders the option steps; advance once the (async) render settles
      if(group===sizeCid){ var p=selectSize(optId); var go=function(){ cfgAdvance(cfgRowsList()[0]); }; if(p&&p.then){ p.then(go); } else { go(); } return; }
      sel[group]=optId; markSelected(group,optId);
      var comp=components.filter(function(c){ return c.id===group; })[0];
      var m=meta[optId]; if(comp) setSelLabel('sel-'+comp.key, m?m.name:('#'+optId));
      recalc();
      cfgAdvance(card.closest('.cfg-row'));   // collapse this step, open the next
    });
    // add to basket → native composite add-to-cart URL
    if(elAdd) elAdd.addEventListener('click',function(){ var u=cartUrl(); if(u) window.location.href=u; });
    // finance / cash toggle (hidden by default)
    document.querySelectorAll('.cfg-summary .ptoggle button').forEach(function(b){ b.addEventListener('click',function(){ document.querySelectorAll('.cfg-summary .ptoggle button').forEach(function(x){ x.classList.remove('on'); }); b.classList.add('on'); recalc(); }); });
    // route page CTAs to configurator (add-to-basket excluded via .cfgadd)
    document.querySelectorAll('.subnav .buy, .pricepill .go, .buybar .go, .final .go').forEach(function(b){ b.addEventListener('click',function(){ var c=document.getElementById('configure'); if(c) c.scrollIntoView({behavior:'smooth'}); }); });

    recalc(); load();
  })();

  // mobile menu toggle
  (function(){
    var m=document.querySelector('.mainhead .menu'), p=document.getElementById('primnav');
    if(m&&p){ m.addEventListener('click',function(){ var o=p.classList.toggle('open'); m.setAttribute('aria-expanded',o); }); }
  })();

  // customer support widget
  (function(){
    var sup=document.getElementById('support'); if(!sup)return;
    var launch=sup.querySelector('.launch');
    function toggle(force){ var open=(force!==undefined)?force:!sup.classList.contains('open'); sup.classList.toggle('open',open); if(launch) launch.setAttribute('aria-expanded',open); }
    if(launch) launch.addEventListener('click',function(){ toggle(); });
    document.querySelectorAll('.supporttrigger').forEach(function(b){ b.addEventListener('click',function(){ toggle(true); }); });
    if(launch && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) launch.classList.toggle('show-phone'); }, 3000); }
  })();

  // play composite cladding video on scroll into view, freeze on last frame
  (function(){
    var v=document.querySelector('.clad-video'); if(!v) return;
    var played=false;
    var io=new IntersectionObserver(function(es){
      es.forEach(function(e){
        if(e.isIntersecting && !played){ played=true; var p=v.play(); if(p&&p.catch){ p.catch(function(){}); } io.unobserve(v); }
      });
    }, { threshold:0.4 });
    io.observe(v);
  })();

  // gallery image disclaimer popover — hover shows it (desktop); click/tap toggles; outside-click / Esc closes.
  (function(){
    var d=document.getElementById('cfgDisc'); if(!d) return;
    var b=d.querySelector('.cfg-disc-btn'); if(!b) return;
    function close(){ d.classList.remove('open'); b.setAttribute('aria-expanded','false'); }
    b.addEventListener('click', function(e){ e.stopPropagation(); var open=d.classList.toggle('open'); b.setAttribute('aria-expanded', open?'true':'false'); });
    document.addEventListener('click', function(e){ if(!d.contains(e.target)) close(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
  })();

