/* Project Timber — account page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  (function(){
    var hs=document.getElementById('hsearch'); if(!hs) return;
    var inp=hs.querySelector('input');
    function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
    document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
  })();


  // header menu + support
  (function(){ var m=document.querySelector('.mainhead .menu'), p=document.getElementById('primnav'); if(m&&p){ m.addEventListener('click',function(){ var o=p.classList.toggle('open'); m.setAttribute('aria-expanded',o); }); } })();
  (function(){ var sup=document.getElementById('support'); if(!sup) return; var l=sup.querySelector('.launch');
    function t(f){ var o=(f!==undefined)?f:!sup.classList.contains('open'); sup.classList.toggle('open',o); if(l) l.setAttribute('aria-expanded',o); }
    if(l) l.addEventListener('click',function(){ t(); }); document.querySelectorAll('.supporttrigger').forEach(function(b){ b.addEventListener('click',function(){ t(true); }); });
    if(l && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) l.classList.toggle('show-phone'); }, 3000); } })();

  // account panel switching
  (function(){
    var nav=document.getElementById('accNav'), panels=document.querySelectorAll('.acc-panel');
    var sidebar=document.getElementById('accSidebar'), mobLabel=document.getElementById('accMobLabel');
    function show(name,label){
      document.querySelectorAll('.acc-nav button[data-panel]').forEach(function(b){ b.classList.toggle('active', b.dataset.panel===name); });
      panels.forEach(function(p){ p.classList.toggle('active', p.dataset.panel===name); });
      if(label) mobLabel.textContent=label;
      sidebar.classList.remove('open');
      window.scrollTo({top:0,behavior:'smooth'});
    }
    nav.addEventListener('click',function(e){ var b=e.target.closest('button[data-panel]'); if(!b) return; show(b.dataset.panel, b.textContent.trim()); });
    // dashboard quick-links
    document.querySelectorAll('[data-jump]').forEach(function(a){ a.addEventListener('click',function(e){ e.preventDefault(); var n=a.dataset.jump; var btn=document.querySelector('.acc-nav button[data-panel="'+n+'"]'); show(n, btn?btn.textContent.trim():''); }); });
    // mobile sidebar toggle
    var mob=document.getElementById('accMobToggle'); if(mob) mob.addEventListener('click',function(){ sidebar.classList.toggle('open'); });
  })();

  // orders: expand detail
  document.querySelectorAll('.vieworder').forEach(function(b){ b.addEventListener('click',function(){
    var row=b.closest('tr'), det=row.nextElementSibling;
    if(det && det.classList.contains('order-detail')){ det.classList.toggle('open'); b.textContent=det.classList.contains('open')?'Hide':'View'; }
  }); });

  // order issues tabs
  document.querySelectorAll('.oi-tab').forEach(function(t){ t.addEventListener('click',function(){
    document.querySelectorAll('.oi-tab').forEach(function(x){ x.classList.remove('active'); }); t.classList.add('active');
    document.querySelectorAll('.oi-panel').forEach(function(p){ p.classList.toggle('active', p.dataset.oi===t.dataset.oi); });
  }); });

  // addresses: edit toggle
  (function(){ var view=document.getElementById('addrView'), form=document.getElementById('addrForm'), cancel=document.getElementById('addrCancel');
    document.querySelectorAll('.editaddr').forEach(function(b){ b.addEventListener('click',function(){ view.style.display='none'; form.style.display='block'; }); });
    if(cancel) cancel.addEventListener('click',function(){ form.style.display='none'; view.style.display=''; });
  })();

  (function(){
    var sel=document.getElementById('aiSelect'), fr=document.getElementById('aiFrame'), op=document.getElementById('aiOpen');
    if(!sel) return;
    sel.addEventListener('change',function(){ var src=sel.options[sel.selectedIndex].getAttribute('data-src'); if(fr) fr.src=src; if(op) op.href=src; });
  })();
