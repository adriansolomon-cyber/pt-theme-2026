/* Project Timber — home page scripts.
   Loaded by assets/js/include.js AFTER partials are injected, so code that
   touches the header / cart drawer can rely on those elements existing. */

  (function(){
    var hs=document.getElementById('hsearch'); if(!hs) return;
    var inp=hs.querySelector('input');
    function toggle(){ if(hs.hasAttribute('hidden')){ hs.removeAttribute('hidden'); if(inp) inp.focus(); } else { hs.setAttribute('hidden',''); } }
    document.querySelectorAll('.searchic, .mainhead .search').forEach(function(b){ b.addEventListener('click', toggle); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !hs.hasAttribute('hidden')) hs.setAttribute('hidden',''); });
  })();


  (function(){
    var v=document.getElementById('heroVideo'), b=document.getElementById('heroVtoggle'); if(!v||!b) return;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    function setPaused(p){ b.classList.toggle('paused',p); b.setAttribute('aria-label', p?'Play background video':'Pause background video'); }
    function apply(){ if(reduce.matches){ v.pause(); v.removeAttribute('autoplay'); setPaused(true); } }
    apply(); if(reduce.addEventListener) reduce.addEventListener('change',apply);
    b.addEventListener('click',function(){ if(v.paused){ v.play(); setPaused(false); } else { v.pause(); setPaused(true); } });
  })();


  (function(){
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.exp-tab'));
    if(!tabs.length) return;
    var panels = {};
    tabs.forEach(function(t){ panels[t.dataset.panel] = document.getElementById('panel-'+t.dataset.panel); });
    function select(name){
      tabs.forEach(function(t){ var on = t.dataset.panel===name; t.setAttribute('aria-selected', on?'true':'false'); t.tabIndex = on?0:-1; });
      Object.keys(panels).forEach(function(k){ if(panels[k]) panels[k].hidden = (k!==name); });
    }
    tabs.forEach(function(t){ t.addEventListener('click', function(){ select(t.dataset.panel); }); });
    var bar = document.querySelector('.exp-tabs');
    if(bar) bar.addEventListener('keydown', function(e){
      var i = tabs.indexOf(document.activeElement); if(i<0) return;
      if(e.key==='ArrowRight'||e.key==='ArrowLeft'){ e.preventDefault(); var n=(i+(e.key==='ArrowRight'?1:tabs.length-1))%tabs.length; tabs[n].focus(); select(tabs[n].dataset.panel); }
    });
    var m = new Date().getMonth(); // 0 = Jan
    // Seasonal default: Mar–May workshop · Jun–Aug summerhouse · Sep–Oct storage · Nov–Feb office (insulated/all-season)
    var def = (m>=2 && m<=4) ? 'workshop'
            : (m>=5 && m<=7) ? 'summerhouse'
            : (m>=8 && m<=9) ? 'storage'
            : 'office';
    select(def);
  })();


  (function(){
    var book=document.getElementById('book'); if(!book) return;
    var lastFocus=null;
    function open(){ lastFocus=document.activeElement; resetFlow(); book.classList.add('open'); book.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; var x=book.querySelector('.book-x'); if(x) x.focus(); }
    function close(){ book.classList.remove('open'); book.setAttribute('aria-hidden','true'); document.body.style.overflow=''; if(lastFocus) lastFocus.focus(); }
    [].forEach.call(document.querySelectorAll('.ss-open'),function(b){ b.addEventListener('click',open); });
    book.addEventListener('click',function(e){ if(e.target.hasAttribute('data-close')) close(); });
    document.addEventListener('keydown',function(e){ if(e.key==='Escape' && book.classList.contains('open')) close(); });

    var grid=document.getElementById('calGrid'), title=document.getElementById('calTitle');
    var prevBtn=book.querySelector('[data-prev]'), nextBtn=book.querySelector('[data-next]');
    var slots=document.getElementById('slots'), slotList=document.getElementById('slotList'), slotDate=document.getElementById('slotDate');
    var form=document.getElementById('bookForm'), done=document.getElementById('bookDone'), sum=document.getElementById('bookSum'), doneMsg=document.getElementById('doneMsg');
    var months=['January','February','March','April','May','June','July','August','September','October','November','December'];
    var dows=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var times=['9:30','10:30','11:30','13:00','14:00','15:00','16:00'];
    var today=new Date(); today.setHours(0,0,0,0);
    var minMonth=new Date(today.getFullYear(),today.getMonth(),1);
    var view=new Date(minMonth), selDate=null, selTime=null;
    function sameMonth(a,b){ return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth(); }
    function fmt(d){ return dows[d.getDay()]+' '+d.getDate()+' '+months[d.getMonth()]; }
    function renderCal(){
      title.textContent=months[view.getMonth()]+' '+view.getFullYear();
      prevBtn.disabled=sameMonth(view,minMonth);
      grid.innerHTML='';
      var first=new Date(view.getFullYear(),view.getMonth(),1);
      var startDow=(first.getDay()+6)%7, dim=new Date(view.getFullYear(),view.getMonth()+1,0).getDate();
      for(var i=0;i<startDow;i++){ var e=document.createElement('div'); e.className='cal-cell empty'; grid.appendChild(e); }
      for(var d=1;d<=dim;d++){
        var cell=document.createElement('button'); cell.type='button'; cell.className='cal-cell'; cell.textContent=d;
        var date=new Date(view.getFullYear(),view.getMonth(),d);
        if(date<today || date.getDay()===0){ cell.disabled=true; }
        else { (function(date,cell){ cell.addEventListener('click',function(){ selectDate(date,cell); }); })(date,cell); }
        if(selDate && date.getTime()===selDate.getTime()) cell.classList.add('sel');
        grid.appendChild(cell);
      }
    }
    function selectDate(date,cell){
      selDate=date; selTime=null;
      [].forEach.call(grid.querySelectorAll('.cal-cell.sel'),function(c){ c.classList.remove('sel'); });
      cell.classList.add('sel');
      slotDate.textContent=fmt(date); slotList.innerHTML='';
      times.forEach(function(t){ var b=document.createElement('button'); b.type='button'; b.className='slot'; b.textContent=t;
        b.addEventListener('click',function(){ selTime=t; [].forEach.call(slotList.querySelectorAll('.slot.sel'),function(s){s.classList.remove('sel');}); b.classList.add('sel'); showForm(); });
        slotList.appendChild(b);
      });
      slots.hidden=false; form.hidden=true; done.hidden=true;
      slots.scrollIntoView({behavior:'smooth',block:'nearest'});
    }
    function showForm(){ sum.innerHTML='Your visit: <b>'+fmt(selDate)+' at '+selTime+'</b>'; form.hidden=false; done.hidden=true; form.scrollIntoView({behavior:'smooth',block:'nearest'}); }
    function resetFlow(){ selDate=null; selTime=null; view=new Date(minMonth); slots.hidden=true; form.hidden=true; done.hidden=true; renderCal(); }
    prevBtn.addEventListener('click',function(){ if(sameMonth(view,minMonth))return; view.setMonth(view.getMonth()-1); renderCal(); });
    nextBtn.addEventListener('click',function(){ view.setMonth(view.getMonth()+1); renderCal(); });
    form.addEventListener('submit',function(e){ e.preventDefault(); doneMsg.textContent='Thanks — we’ll email you to confirm your visit for '+fmt(selDate)+' at '+selTime+'.'; slots.hidden=true; form.hidden=true; done.hidden=false; done.scrollIntoView({behavior:'smooth',block:'nearest'}); });
    renderCal();
  })();


  // mobile menu toggle
  (function(){ var m=document.querySelector('.mainhead .menu'), p=document.getElementById('primnav');
    if(m&&p){ m.addEventListener('click',function(){ var o=p.classList.toggle('open'); m.setAttribute('aria-expanded',o); }); } })();
  // support widget
  (function(){
    var sup=document.getElementById('support'); if(!sup) return;
    var launch=sup.querySelector('.launch');
    function toggle(force){ var open=(force!==undefined)?force:!sup.classList.contains('open'); sup.classList.toggle('open',open); if(launch) launch.setAttribute('aria-expanded',open); }
    if(launch) launch.addEventListener('click',function(){ toggle(); });
    document.querySelectorAll('.supporttrigger').forEach(function(b){ b.addEventListener('click',function(){ toggle(true); }); });
    document.addEventListener('keydown',function(e){ if(e.key==='Escape' && sup.classList.contains('open')){ toggle(false); if(launch) launch.focus(); } });
    if(launch && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){ setInterval(function(){ if(!sup.classList.contains('open')) launch.classList.toggle('show-phone'); }, 3000); }
  })();

