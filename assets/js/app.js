(function(){
  // 各功能块独立初始化，单个模块报错不影响其余模块（统一在文件末尾逐个 try/catch 调用）。
  function reportError(scope,error){
    if(window.console&&typeof console.warn==='function'){console.warn('[yimai] '+scope+' 初始化失败',error)}
  }

  /* 移动端菜单：切换显示并同步 aria-expanded / aria-label（标记见 header.php） */
  function initMenu(){
    var toggle=document.querySelector('[data-menu-toggle]');
    var menu=document.querySelector('[data-mobile-menu]');
    if(!toggle||!menu)return;
    var sync=function(){
      var open=menu.classList.contains('open');
      toggle.setAttribute('aria-expanded',open?'true':'false');
      toggle.setAttribute('aria-label',open?'关闭菜单':'打开菜单');
    };
    toggle.addEventListener('click',function(){
      menu.classList.toggle('open');
      sync();
    });
    sync();
  }

  /* 滚动入场动效：IntersectionObserver 不可用或初始化失败时，直接显示全部内容 */
  function initReveal(){
    var targets=document.querySelectorAll('.reveal');
    var showAll=function(){targets.forEach(function(el){el.classList.add('visible')})};
    if(!targets.length)return;
    if(typeof IntersectionObserver==='undefined'){showAll();return}
    var observer;
    try{
      observer=new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if(entry.isIntersecting){
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      },{threshold:.12});
    }catch(error){
      reportError('reveal',error);
      showAll();
      return;
    }
    if(!observer){showAll();return}
    try{
      targets.forEach(function(el){observer.observe(el)});
    }catch(error){
      reportError('reveal',error);
      showAll();
    }
  }

  /* 图床图片加载失败时，按兜底映射换回本地地址（footer.php 输出 YIMAI_IMG_FALLBACK） */
  function initImageFallback(){
    document.addEventListener('error', function(e){
      var el = e.target;
      if (!el || el.tagName !== 'IMG' || !window.YIMAI_IMG_FALLBACK) return;
      var src = el.currentSrc || el.src;
      if (!src) return;
      var fb = window.YIMAI_IMG_FALLBACK[src];
      if (fb) { el.src = fb; }
    }, true);
  }

  /* 活动公告：每次进入首页自动弹一次（关闭后本次停留不再打扰）；预约页活动条点击打开弹窗 */
  function initNotice(){
    var notice=document.querySelector('[data-notice-modal]');
    if(!notice)return;
    var closeBtn=notice.querySelector('[data-notice-close]');
    var lastTrigger=null;
    var openNotice=function(trigger){
      lastTrigger=trigger||null;
      notice.classList.add('open');
      // 打开后把焦点移入弹窗（关闭按钮），关闭时归还给触发元素
      if(closeBtn&&typeof closeBtn.focus==='function'){
        try{closeBtn.focus()}catch(error){reportError('notice.focus',error)}
      }
    };
    var closeNotice=function(){
      if(!notice.classList.contains('open'))return;
      notice.classList.remove('open');
      var back=lastTrigger;
      lastTrigger=null;
      if(back&&back.isConnected&&typeof back.focus==='function'){
        try{back.focus()}catch(error){reportError('notice.focus',error)}
      }else if(closeBtn&&document.activeElement===closeBtn&&typeof closeBtn.blur==='function'){
        closeBtn.blur();
      }
    };
    if(document.body.classList.contains('home')){
      setTimeout(function(){openNotice(null)},800);
    }
    notice.addEventListener('click',function(e){if(e.target===notice){closeNotice()}});
    notice.querySelectorAll('[data-notice-close]').forEach(function(btn){btn.addEventListener('click',function(){closeNotice()})});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&notice.classList.contains('open')){closeNotice()}});
    // 预约页活动条：无独立链接（href="#"）时点击打开弹窗
    var strip=document.querySelector('[data-activity-strip]');
    if(strip){
      strip.addEventListener('click',function(e){
        var href=strip.getAttribute('href');
        if(!href||href==='#'){
          e.preventDefault();
          openNotice(strip);
        }
      });
    }
  }

  /* 师资 Tab：点击/方向键切换，同步 ARIA（标记见 page-instructors.php） */
  function initTeacherTabs(){
    document.querySelectorAll('[data-instructors]').forEach(function(root){
      var tabs=Array.prototype.slice.call(root.querySelectorAll('[data-teacher-tab]'));
      var panels=Array.prototype.slice.call(root.querySelectorAll('[data-teacher-panel]'));
      if(!tabs.length||!panels.length)return;
      var select=function(index,focus,scroll){
        var target=Math.max(0,Math.min(tabs.length-1,index));
        tabs.forEach(function(tab,i){
          var on=(i===target);
          tab.classList.toggle('active',on);
          tab.setAttribute('aria-selected',on?'true':'false');
          tab.setAttribute('tabindex',on?'0':'-1');
        });
        panels.forEach(function(panel,i){
          var on=(i===target);
          panel.classList.toggle('active',on);
          // hidden 让未选面板对辅助技术彻底不可达（显示仍由 .teacher-detail.active 控制）
          if(on){panel.removeAttribute('hidden')}else{panel.setAttribute('hidden','')}
        });
        if(focus&&tabs[target]){tabs[target].focus()}
        if(scroll){
          var panel=panels[target];
          if(panel&&typeof panel.scrollIntoView==='function'){panel.scrollIntoView({behavior:'smooth',block:'nearest'})}
        }
      };
      tabs.forEach(function(tab,i){
        tab.addEventListener('click',function(){select(i,false,true)});
        tab.addEventListener('keydown',function(e){
          var key=e.key;
          if(key==='ArrowRight'||key==='ArrowDown'){e.preventDefault();select(i+1,true,false)}
          else if(key==='ArrowLeft'||key==='ArrowUp'){e.preventDefault();select(i-1,true,false)}
          else if(key==='Home'){e.preventDefault();select(0,true,false)}
          else if(key==='End'){e.preventDefault();select(tabs.length-1,true,false)}
        });
      });
      select(0,false,false);
    });
  }

  /* 预约表单：AJAX 提交，提交期间锁定按钮防止重复提交 */
  function initBookingForms(){
    document.querySelectorAll('[data-booking-form]').forEach(function(form){
      var submitting=false;
      form.addEventListener('submit',function(event){
        event.preventDefault();
        if(submitting)return;
        var msg=form.querySelector('[data-form-message]');
        var button=form.querySelector('button[type="submit"]');
        if(typeof fetch!=='function'){
          if(msg){msg.textContent='当前浏览器不支持在线提交，请电话联系门店。'}
          return;
        }
        submitting=true;
        if(msg){msg.textContent='提交中...'}
        if(button){button.disabled=true}
        // 表单内有 name="action" 的隐藏字段（admin-ajax 必需），form.action 会被该控件遮蔽
        // （返回 input 元素，拼出 [object HTMLInputElement] 的 404 地址），必须读属性或用 localize 的地址
        var endpoint=(window.yimaiAjax&&yimaiAjax.url)||form.getAttribute('action');
        fetch(endpoint,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(res){return res.text().then(function(text){var data;try{data=JSON.parse(text)}catch(e){throw{message:'提交失败，请稍后再试或电话联系门店。'}}if(!res.ok){throw (data&&data.data&&data.data.message)?{message:data.data.message}:data}return data})}).then(function(data){if(msg){msg.textContent=(data&&data.data&&data.data.message)||(data&&data.message)||'已提交，我们会尽快联系你。'}form.reset()}).catch(function(error){if(msg){msg.textContent=(error&&error.data&&error.data.message)||(error&&error.message)||'提交失败，请稍后再试或电话联系门店。'}}).finally(function(){submitting=false;if(button){button.disabled=false}})
      })
    })
  }

  [initMenu,initReveal,initImageFallback,initNotice,initTeacherTabs,initBookingForms].forEach(function(init){
    try{
      init();
    }catch(error){
      reportError(init.name||'module',error);
    }
  });
})();
