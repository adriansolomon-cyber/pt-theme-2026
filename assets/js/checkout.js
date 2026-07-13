/* Project Timber — checkout page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  // payment option selection
  document.querySelectorAll('.pay-opt [type=radio]').forEach(function(r){ r.addEventListener('change',function(){
    document.querySelectorAll('.pay-opt').forEach(function(o){ o.classList.toggle('sel', o.contains(r) && r.checked); });
  }); });
  // consent read more
  (function(){ var c=document.getElementById('consent'), m=document.getElementById('consentMore'); if(m) m.addEventListener('click',function(){ c.classList.toggle('open'); m.textContent=c.classList.contains('open')?'Read less':'Read more'; }); })();
  // remove → back to (empty) basket
  document.querySelectorAll('.sum-item .rm').forEach(function(b){ b.addEventListener('click',function(){ window.location.href='projecttimber-garden-offices.html'; }); });
  // support widget
  (function(){ var sup=document.getElementById('support'); if(!sup) return; var l=sup.querySelector('.launch');
    function t(f){ var o=(f!==undefined)?f:!sup.classList.contains('open'); sup.classList.toggle('open',o); if(l) l.setAttribute('aria-expanded',o); }
    if(l) l.addEventListener('click',function(){ t(); });
    if(l && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) l.classList.toggle('show-phone'); }, 3000); } })();


  (function(){
    var s=document.querySelector('.qstep'); if(!s) return;
    var dec=s.querySelector('.q-dec'), inc=s.querySelector('.q-inc'), n=s.querySelector('.q-n');
    var unitEl=document.querySelector('.sum-item .pr');
    var badge=document.querySelector('.sum-item .thumb .qty');
    var subEl=document.querySelector('.sum-tot .ln b');
    var totEl=document.querySelector('.sum-tot .grand .v');
    var vatEl=document.querySelector('.sum-tot .vat');
    var unit=unitEl?parseFloat(unitEl.textContent.replace(/[^0-9.]/g,'')):0, q=1;
    function m(x){ return '£'+x.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function u(){ n.textContent=q; if(badge)badge.textContent=q; dec.disabled=q<=1; var t=unit*q; if(subEl)subEl.textContent=m(t); if(totEl)totEl.textContent=m(t); if(vatEl)vatEl.textContent='Includes '+m(t/6)+' VAT'; }
    dec.addEventListener('click',function(){ if(q>1){q--;u();} });
    inc.addEventListener('click',function(){ q++; u(); });
    u();
  })();
