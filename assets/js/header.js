/* Project Timber — header search toggle.
   The same open/close behaviour that lives in the per-page scripts, extracted
   for templates that don't load one of those (e.g. search.php). A one-time
   binding guard on #hsearch keeps it from double-binding if another script on
   the page also wires the toggle. */
(function(){
  var hs=document.getElementById('hsearch'); if(!hs) return;
  if(hs.getAttribute('data-pt-search-bound')) return;
  hs.setAttribute('data-pt-search-bound','1');
  var inp=hs.querySelector('input');
  function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
  document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
})();
