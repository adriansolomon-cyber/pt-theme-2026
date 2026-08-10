/* Project Timber — header chrome behaviour (search toggle + mobile menu +
   support widget). This is the SAME wiring that lives inline in the per-page
   scripts (home/product/category.js). It is enqueued site-wide ONLY on
   templates that load none of those — Cart, Checkout, My Account, CMS pages
   (page.php), and the blog/archive/404 fallback (index.php) — so the header
   stays interactive everywhere. One-time binding guards on each element make
   it safe even if another script on the page also wires the same control. */

// search toggle
(function(){
  var hs=document.getElementById('hsearch'); if(!hs) return;
  if(hs.getAttribute('data-pt-search-bound')) return;
  hs.setAttribute('data-pt-search-bound','1');
  var inp=hs.querySelector('input');
  function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
  document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
})();

// mobile menu toggle
(function(){
  var m=document.querySelector('.mainhead .menu'), p=document.getElementById('primnav');
  if(!m||!p) return;
  if(m.getAttribute('data-pt-menu-bound')) return;
  m.setAttribute('data-pt-menu-bound','1');
  m.addEventListener('click',function(){ var o=p.classList.toggle('open'); m.setAttribute('aria-expanded',o); });
})();

// support widget
(function(){
  var sup=document.getElementById('support'); if(!sup) return;
  if(sup.getAttribute('data-pt-support-bound')) return;
  sup.setAttribute('data-pt-support-bound','1');
  var launch=sup.querySelector('.launch');
  function toggle(force){ var open=(force!==undefined)?force:!sup.classList.contains('open'); sup.classList.toggle('open',open); if(launch) launch.setAttribute('aria-expanded',open); }
  if(launch) launch.addEventListener('click',function(){ toggle(); });
  document.querySelectorAll('.supporttrigger').forEach(function(b){ b.addEventListener('click',function(){ toggle(true); }); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape' && sup.classList.contains('open')){ toggle(false); if(launch) launch.focus(); } });
  if(launch && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) launch.classList.toggle('show-phone'); }, 3000); }
})();
