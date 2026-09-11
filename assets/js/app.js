(function(){
  var toggle=document.querySelector('[data-menu-toggle]');
  var menu=document.querySelector('[data-mobile-menu]');
  if(toggle&&menu){toggle.addEventListener('click',function(){menu.classList.toggle('open')})}
  var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target)}})},{threshold:.12});
  document.querySelectorAll('.reveal').forEach(function(el){observer.observe(el)});
  document.querySelectorAll('[data-instructors]').forEach(function(root){
    var tabs=root.querySelectorAll('[data-teacher-tab]');
    var panels=root.querySelectorAll('[data-teacher-panel]');
    tabs.forEach(function(tab){tab.addEventListener('click',function(){var id=tab.getAttribute('data-teacher-tab');tabs.forEach(function(t){t.classList.remove('active')});panels.forEach(function(p){p.classList.remove('active')});tab.classList.add('active');var panel=root.querySelector('[data-teacher-panel="'+id+'"]');if(panel){panel.classList.add('active');panel.scrollIntoView({behavior:'smooth',block:'nearest'})}})})
  });
  document.querySelectorAll('[data-booking-form]').forEach(function(form){
    form.addEventListener('submit',function(event){
      event.preventDefault();
      var msg=form.querySelector('[data-form-message]');
      var button=form.querySelector('button[type="submit"]');
      if(msg){msg.textContent='提交中...'}
      if(button){button.disabled=true}
      fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})}).then(function(data){if(msg){msg.textContent=(data&&data.data&&data.data.message)||(data&&data.message)||'已提交，我们会尽快联系你。'}form.reset()}).catch(function(error){if(msg){msg.textContent=(error&&error.data&&error.data.message)||(error&&error.message)||'提交失败，请稍后再试或电话联系门店。'}}).finally(function(){if(button){button.disabled=false}})
    })
  })
})();
