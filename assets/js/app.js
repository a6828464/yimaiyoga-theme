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
  // 图床图片加载失败时，按兜底映射换回本地地址（footer.php 输出 YIMAI_IMG_FALLBACK）
  document.addEventListener('error', function(e){
    var el = e.target;
    if (!el || el.tagName !== 'IMG' || !window.YIMAI_IMG_FALLBACK) return;
    var src = el.currentSrc || el.src;
    if (!src) return;
    var fb = window.YIMAI_IMG_FALLBACK[src];
    if (fb) { el.src = fb; }
  }, true);
  // 活动公告：首页首次进站弹窗（每个活动仅弹一次），活动条点击打开弹窗
  var notice=document.querySelector('[data-notice-modal]');
  if(notice){
    var noticeId=notice.getAttribute('data-notice-id')||'';
    var SEEN_KEY='yimai_notice_seen';
    var seenId=function(){try{return localStorage.getItem(SEEN_KEY)}catch(e){return null}};
    var markSeen=function(){try{localStorage.setItem(SEEN_KEY,noticeId)}catch(e){}};
    var openNotice=function(){notice.classList.add('open')};
    var closeNotice=function(mark){notice.classList.remove('open');if(mark!==false){markSeen()}};
    // 仅首页自动弹出；进入后稍作延迟，避免打断首屏
    if(document.body.classList.contains('home')&&seenId()!==noticeId){
      setTimeout(openNotice,800);
    }
    notice.addEventListener('click',function(e){if(e.target===notice){closeNotice()}});
    notice.querySelectorAll('[data-notice-close]').forEach(function(btn){btn.addEventListener('click',function(){closeNotice()})});
    var cta=notice.querySelector('[data-notice-cta]');
    if(cta){cta.addEventListener('click',function(){markSeen()})}
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&notice.classList.contains('open')){closeNotice()}});
  }
  var strip=document.querySelector('[data-activity-strip]');
  if(strip&&notice){
    strip.addEventListener('click',function(e){
      var href=strip.getAttribute('href');
      if(!href||href==='#'){
        e.preventDefault();
        notice.classList.add('open');
      }
    });
  }
  document.querySelectorAll('[data-booking-form]').forEach(function(form){
    form.addEventListener('submit',function(event){
      event.preventDefault();
      var msg=form.querySelector('[data-form-message]');
      var button=form.querySelector('button[type="submit"]');
      if(msg){msg.textContent='提交中...'}
      if(button){button.disabled=true}
      // 表单内有 name="action" 的隐藏字段（admin-ajax 必需），form.action 会被该控件遮蔽
      // （返回 input 元素，拼出 [object HTMLInputElement] 的 404 地址），必须读属性或用 localize 的地址
      var endpoint=(window.yimaiAjax&&yimaiAjax.url)||form.getAttribute('action');
      fetch(endpoint,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(res){return res.text().then(function(text){var data;try{data=JSON.parse(text)}catch(e){throw{message:'提交失败，请稍后再试或电话联系门店。'}}if(!res.ok){throw (data&&data.data&&data.data.message)?{message:data.data.message}:data}return data})}).then(function(data){if(msg){msg.textContent=(data&&data.data&&data.data.message)||(data&&data.message)||'已提交，我们会尽快联系你。'}form.reset()}).catch(function(error){if(msg){msg.textContent=(error&&error.data&&error.data.message)||(error&&error.message)||'提交失败，请稍后再试或电话联系门店。'}}).finally(function(){if(button){button.disabled=false}})
    })
  })
})();
