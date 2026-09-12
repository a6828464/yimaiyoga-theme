(function(){
  var root=document.querySelector('[data-admin-form]');
  if(!root){return}
  var hidden=root.querySelector('[data-config-json]');
  var message=root.querySelector('[data-admin-message]');
  if(!hidden){return}
  var config=window.CONFIG||JSON.parse(hidden.value||'{}');
  var IMGBED=window.YIMAI_IMGBED||{domain:'',map:{}};
  var REVERSE={};
  Object.keys(IMGBED.map||{}).forEach(function(local){
    REVERSE[IMGBED.domain+'/'+IMGBED.map[local]]=local;
  });

  /* ================= 基础工具 ================= */
  function setPath(path,value){
    var parts=path.split('.');
    var target=config;
    for(var i=0;i<parts.length-1;i++){if(!target[parts[i]]||typeof target[parts[i]]!=='object'){target[parts[i]]={}}target=target[parts[i]]}
    target[parts[parts.length-1]]=value;
  }
  function getPath(path){
    var parts=path.split('.');
    var target=config;
    for(var i=0;i<parts.length;i++){if(target==null)return undefined;target=target[parts[i]]}
    return target;
  }
  function esc(s){
    return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function msg(t){if(message){message.textContent=t||''}}
  function fieldHtml(path,label,type,value,extra){
    var v=value==null?'':value;
    var t=type||'text';
    if(t==='check'){
      return '<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ink);margin-top:6px">'+esc(label||'')
        +'<input type="checkbox" data-path="'+path+'" '+(v?'checked':'')+'></label>';
    }
    var ta = t==='textarea' ? '<textarea data-path="'+path+'" '+ (extra||'') +'>'+esc(v)+'</textarea>' : '<input type="'+t+'" data-path="'+path+'" value="'+esc(v)+'" '+ (extra||'') +'>';
    return '<label>'+esc(label||'')+ta+'</label>';
  }
  /* 列表编辑器的图片字段：预览 + 地址 + 本地/图床切换 + 上传 */
  function imageBlockHtml(path,value){
    var v=value==null?'':String(value);
    var isRemote=/^https?:\/\//i.test(v);
    var src=v?(v.indexOf('http')===0?v:(window.YIMAI_URI||'')+'/assets/images'+v):'';
    var html='<div class="img-preview" data-preview="'+path+'" style="height:80px;max-width:220px">'
      +(src?'<img src="'+esc(src)+'" onerror="this.parentNode.textContent=\'无图片\'">':'无图片')
      +'</div>';
    html+='<input type="text" data-path="'+path+'" value="'+esc(v)+'" placeholder="留空无图 / /uploads/… / https://…">';
    html+='<div class="src-toggle" data-src-toggle="'+path+'">'
      +'<button type="button" data-src-btn="local" class="'+(isRemote?'':'active')+'">本地</button>'
      +'<button type="button" data-src-btn="imgbed" class="'+(isRemote?'active':'')+'">图床</button>'
      +'<span class="src-hint" data-src-hint="'+path+'"></span></div>';
    html+='<div class="file-row"><input type="file" data-upload-for="'+path+'" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="'+path+'">上传图片</button><button type="button" class="upload-btn" data-library-for="'+path+'">图片库</button></div>';
    return '<div style="margin-top:10px">'+html+'</div>';
  }

  /* ================= Tab 切换 + 手机端抽屉 ================= */
  var tabs=document.querySelectorAll('[data-tab]');
  var drawer=document.querySelector('[data-drawer]');
  var backdrop=document.querySelector('.drawer-backdrop');
  function openDrawer(){if(drawer){drawer.classList.add('open')}if(backdrop){backdrop.classList.add('open')}}
  function closeDrawer(){if(drawer){drawer.classList.remove('open')}if(backdrop){backdrop.classList.remove('open')}}
  document.querySelectorAll('[data-drawer-open]').forEach(function(b){b.addEventListener('click',openDrawer)});
  document.querySelectorAll('[data-drawer-close]').forEach(function(b){b.addEventListener('click',closeDrawer)});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeDrawer();closeLibrary()}});
  function showTab(name){
    document.querySelectorAll('[data-tab]').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-tab')===name)});
    document.querySelectorAll('[data-panel]').forEach(function(p){p.classList.toggle('active',p.getAttribute('data-panel')===name)});
    var btn=document.querySelector('[data-tab="'+name+'"]');
    var title=document.querySelector('[data-main-title]');
    if(btn&&title&&btn.getAttribute('data-title')){title.textContent=btn.getAttribute('data-title')}
    closeDrawer();
  }
  tabs.forEach(function(b){b.addEventListener('click',function(){showTab(b.getAttribute('data-tab'))})});

  /* ================= 图片预览 ================= */
  function updatePreview(path){
    var box=document.querySelector('[data-preview="'+path+'"]');
    if(!box)return;
    var v=String(getPath(path)||'');
    if(v){
      var url=(v.indexOf('http')===0)?v:(window.YIMAI_URI||'')+'/assets/images'+v;
      box.innerHTML='<img src="'+esc(url)+'" onerror="this.parentNode.textContent=\'图片加载失败\'">';
    } else {box.innerHTML='无图片'}
  }

  /* ================= 本地 / 图床切换 ================= */
  function srcHint(path,text){
    var el=root.querySelector('[data-src-hint="'+path+'"]');
    if(el){el.textContent=text||''}
  }
  /* 当前应高亮哪一侧：空字段按上传默认设置 */
  function currentSide(path){
    var v=String(getPath(path)||'');
    if(!v){
      var def=(config.site&&config.site.uploadTarget)||'imgbed';
      return def==='local'?'local':'imgbed';
    }
    return /^https?:\/\//i.test(v)?'imgbed':'local';
  }
  function refreshToggle(path){
    var box=root.querySelector('[data-src-toggle="'+path+'"]');
    if(!box)return;
    var side=currentSide(path);
    box.querySelectorAll('[data-src-btn]').forEach(function(b){
      b.classList.toggle('active',b.getAttribute('data-src-btn')===side);
    });
  }
  function rememberImgbed(localPath,imgbedUrl){
    if(!IMGBED.domain||imgbedUrl.indexOf(IMGBED.domain)!==0)return;
    var p=imgbedUrl.slice(IMGBED.domain.length+1);
    IMGBED.map[localPath]=p;
    REVERSE[imgbedUrl]=localPath;
  }
  function handleSrcBtn(btn){
    var box=btn.closest('[data-src-toggle]');
    if(!box)return;
    var path=box.getAttribute('data-src-toggle');
    var side=btn.getAttribute('data-src-btn');
    var field=root.querySelector('[data-path="'+path+'"]');
    var value=String(getPath(path)||'');
    function applyValue(v,tip){
      if(field){field.value=v}
      setPath(path,v);
      updatePreview(path);
      refreshToggle(path);
      srcHint(path,tip||'');
    }
    if(side==='imgbed'){
      if(!value){srcHint(path,'请先上传或填写本地图片');return}
      if(/^https?:\/\//i.test(value)){
        if(IMGBED.domain&&value.indexOf(IMGBED.domain)===0){return}
        srcHint(path,'这是外部图片链接，与图床无关');return;
      }
      if(!IMGBED.domain){srcHint(path,'请先在「基础与SEO」填写图床地址');return}
      var mapped=IMGBED.map[value];
      if(mapped){applyValue(IMGBED.domain+'/'+mapped,'已切换（保存后生效）');return}
      srcHint(path,'正在同步图床…');
      var fd=new FormData();
      fd.append('csrf_token',window.CSRF_TOKEN||'');
      fd.append('path',value);
      fetch('/admin/imgbed-sync',{method:'POST',body:fd})
        .then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})})
        .then(function(data){
          rememberImgbed(value,data.imgbed);
          applyValue(data.imgbed,'已切换（保存后生效）');
          msg('已同步图床并切换地址（点保存生效）');
        })
        .catch(function(error){srcHint(path,'');msg((error&&error.message)||'同步图床失败')});
      return;
    }
    /* 切回本地 */
    if(!value||!/^https?:\/\//i.test(value)){return}
    var local=REVERSE[value];
    if(local){applyValue(local,'已切换（保存后生效）');msg('已切换到本地地址（点保存生效）')}
    else{srcHint(path,'该地址没有对应的本地文件')}
  }

  /* ================= 字段同步 ================= */
  function syncField(field){
    var path=field.getAttribute('data-path');
    if(!path||path==='__root__'){return}
    var value=field.value;
    if(field.getAttribute('type')==='checkbox'){value=field.checked}
    if(field.hasAttribute('data-array-lines')){value=value.split('\n').map(function(i){return i.trim()}).filter(Boolean)}
    setPath(path,value);
    var p=path.split('.');
    if(p[0]==='site'||p[0]==='images'){updatePreview(path)}
    if(root.querySelector('[data-src-toggle="'+path+'"]')){refreshToggle(path)}
  }
  function syncAll(){
    root.querySelectorAll('[data-path]').forEach(function(f){if(f.getAttribute('data-path')==='__root__'){return}syncField(f)});
    hidden.value=JSON.stringify(config,null,2);
  }

  /* ================= 列表编辑器（增删改） ================= */
  var SCHEMAS={
    studios:{fields:[['name','门店名称','text'],['area','面积','text'],['address','地址','text'],['phone','电话','text']]},
    memberships:{fields:[['name','方案名称','text'],['label','副标题','text'],['feature','特点说明','textarea'],['accent','标签','text']]},
    courseThemes:{fields:[['title','主题名称','text'],['type','类型','text'],['effect','功效','textarea'],['suited','适合人群','textarea'],['image','课程图片','image']]},
    classPaths:{fields:[['title','路径名称','text'],['description','说明','textarea']]},
    instructors:{fields:[['name','姓名','text'],['role','职位','text'],['years','年限','text'],['focus','擅长','text'],['image','照片','image'],['summary','简介','textarea']]},
    faqs:{fields:[['q','问题','text'],['a','回答','textarea']]},
    training_programs:{fields:[['name','方案名称','text'],['price','价格','text'],['label','标签','text'],['description','说明','textarea']]},
    nav_items:{fields:[['label','菜单文字','text'],['href','链接','text']]},
    announcements:{fields:[['title','活动标题','text'],['content','活动内容','textarea'],['image','配图（可选）','image'],['link','跳转链接（可选，如 /booking 或完整网址）','text'],['linkText','按钮文字（默认「查看详情」）','text'],['start','开始日期（可选）','date'],['end','结束日期（可选）','date'],['active','启用','check']]}
  };
  var TAG_FIELDS={instructors:['credentials','specialties'],classPaths:['tags'],training_programs:['points']};
  /* announcements 配置结构是 announcements.items */
  var LIST_ALIASES={announcements:'announcements.items'};
  function listParts(key){return (LIST_ALIASES[key]||key).split('.')}
  function getList(key){
    var t=config,p=listParts(key);
    for(var i=0;i<p.length;i++){if(t==null||typeof t!=='object')return null;t=t[p[i]]}
    return t&&typeof t==='object'?t:null;
  }
  function setList(key,arr){
    var p=listParts(key),t=config;
    for(var i=0;i<p.length-1;i++){if(!t[p[i]]||typeof t[p[i]]!=='object'){t[p[i]]={}}t=t[p[i]]}
    t[p[p.length-1]]=arr;
  }

  function renderList(key){
    var host=root.querySelector('[data-editor-list="'+key+'"]');
    if(!host)return
    var list=getList(key)||[];
    var schema=SCHEMAS[key];
    if(!schema){return}
    host.innerHTML=list.map(function(item,idx){
      var name=item.name||item.label||item.title||item.q||('项目 '+(idx+1));
      var html='<div class="item" data-idx="'+idx+'">';
      html+='<div class="item-head"><b>'+esc(name)+'</b><span class="ops">';
      html+='<button type="button" class="del" data-del-item="'+key+'" data-idx="'+idx+'">删除</button></span></div>';
      html+='<div class="grid2">';
      schema.fields.forEach(function(f){
        var path=key+'.'+idx+'.'+f[0];
        var v=item[f[0]];
        if(key==='faqs'){path=key+'.'+idx+'.'+(f[0]==='q'?'0':'1');v=item[f[0]==='q'?0:1]}
        if(f[2]==='image'){html+=imageBlockHtml(path,v)}
        else if(f[2]==='textarea'){html+=fieldHtml(path,f[1],'textarea',v,'rows="3"')}
        else{html+=fieldHtml(path,f[1],f[2]||'text',v)}
      });
      html+='</div>';
      // tags 编辑器
      if(TAG_FIELDS[key]){
        TAG_FIELDS[key].forEach(function(tf){
          var tags=item[tf]||[];
          html+='<div style="margin-top:10px"><span style="font-size:12px;color:var(--mut)">'+esc(tf)+'（回车添加）</span>';
          html+='<div data-tag-box="'+key+'.'+idx+'.'+tf+'">'+tags.map(function(t){return '<span class="tag">'+esc(t)+'<span class="x" data-tag-del="'+key+'.'+idx+'.'+tf+'" data-tag="'+esc(t)+'">×</span></span>'}).join('')+'</div>';
          html+='<input type="text" data-tag-input="'+key+'.'+idx+'.'+tf+'" placeholder="输入后回车添加" style="margin-top:6px"></div>';
        });
      }
      html+='</div>';
      return html;
    }).join('');
    // 绑定删除
    host.querySelectorAll('[data-del-item]').forEach(function(btn){
      btn.addEventListener('click',function(){
        var k=btn.getAttribute('data-del-item');
        var idx=parseInt(btn.getAttribute('data-idx'),10);
        if(!confirm('确定删除这项吗？'))return
        var arr=getList(k)||[];
        arr.splice(idx,1);
        setList(k,arr);
        renderList(k);
        msg('已删除（未保存）');
      });
    });
    // 绑定 tag 删除
    host.querySelectorAll('[data-tag-del]').forEach(function(x){
      x.addEventListener('click',function(){
        var p=x.getAttribute('data-tag-del').split('.');
        var k=p[0],idx=parseInt(p[1],10),tf=p.slice(2).join('.');
        var arr=getList(k)||[];
        var t=x.getAttribute('data-tag');
        arr[idx][tf]=(arr[idx][tf]||[]).filter(function(i){return i!==t});
        setList(k,arr);
        renderList(k);
      });
    });
    // 绑定 tag 输入
    host.querySelectorAll('[data-tag-input]').forEach(function(inp){
      inp.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return
        e.preventDefault();
        var p=inp.getAttribute('data-tag-input').split('.');
        var k=p[0],idx=parseInt(p[1],10),tf=p.slice(2).join('.');
        var v=inp.value.trim();
        if(!v)return
        var arr=getList(k)||[];
        if(!arr[idx][tf]){arr[idx][tf]=[]}
        arr[idx][tf].push(v);
        setList(k,arr);
        renderList(k);
      });
    });
    // 字段输入即时同步到 config
    host.querySelectorAll('[data-path]').forEach(function(f){
      f.addEventListener('input',function(){try{syncField(f)}catch(e){msg(e.message)}});
      if(f.getAttribute('type')==='checkbox'){f.addEventListener('change',function(){try{syncField(f)}catch(e){msg(e.message)}})}
    });
  }

  // 渲染所有列表
  Object.keys(SCHEMAS).forEach(renderList);

  // 添加按钮
  root.querySelectorAll('[data-add-item]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var key=btn.getAttribute('data-add-item');
      var arr=getList(key)||[];
      var blank={};
      SCHEMAS[key].fields.forEach(function(f){blank[f[0]]=''});
      if(key==='announcements'){
        blank.id='act-'+Date.now();
        blank.active=true;
        blank.linkText='查看详情';
      }
      arr.push(blank);
      setList(key,arr);
      renderList(key);
      msg('已添加空白项（填完保存）');
    });
  });

  /* ================= 普通字段即时同步 ================= */
  root.querySelectorAll('[data-path]').forEach(function(field){
    if(field.getAttribute('data-path')==='__root__'){return}
    field.addEventListener('input',function(){try{syncField(field)}catch(e){msg(e.message)}});
    if(field.getAttribute('type')==='checkbox'){field.addEventListener('change',function(){try{syncField(field)}catch(e){msg(e.message)}})}
  });

  /* ================= 上传（事件委托，静态字段与列表字段通用） ================= */
  function doUpload(input,path){
    var file=input.files&&input.files[0];
    if(!file){return}
    var fd=new FormData();
    fd.append('csrf_token',window.CSRF_TOKEN||'');
    fd.append('file',file);
    fd.append('field',path||'');
    msg('上传中...');
    var side=currentSide(path);
    fetch('/admin/upload',{method:'POST',body:fd}).then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})}).then(function(data){
      var value=data.path;
      if(side==='imgbed'&&data.imgbed){
        value=data.imgbed;
        rememberImgbed(data.path,data.imgbed);
      }
      var field=root.querySelector('[data-path="'+path+'"]');
      if(field){field.value=value}
      setPath(path,value);
      updatePreview(path);
      refreshToggle(path);
      input.value='';
      LIB.data=null; /* 新图已落盘，下次打开图片库重新拉取 */
      msg('已上传：'+value+'（点保存生效）');
    }).catch(function(error){msg(error.message||'上传失败')});
  }
  root.addEventListener('change',function(e){
    var t=e.target;
    if(t&&t.matches&&t.matches('[data-upload-for]')){doUpload(t,t.getAttribute('data-upload-for'))}
  });
  root.addEventListener('click',function(e){
    var trig=e.target.closest?e.target.closest('[data-upload-trigger]'):null;
    if(trig){
      var path=trig.getAttribute('data-upload-trigger');
      var fileInput=root.querySelector('[data-upload-for="'+path+'"]');
      if(fileInput){fileInput.click()}
      return;
    }
    var libBtn=e.target.closest?e.target.closest('[data-library-for]'):null;
    if(libBtn){openLibrary(libBtn.getAttribute('data-library-for'));return}
    var btn=e.target.closest?e.target.closest('[data-src-btn]'):null;
    if(btn){handleSrcBtn(btn);return}
    var delImg=e.target.closest?e.target.closest('[data-del-studio-img]'):null;
    if(delImg){
      if(confirm('确定删除这张门店空间图吗？')){
        var di=parseInt(delImg.getAttribute('data-del-studio-img'),10);
        config.images=config.images||{};
        if(!Array.isArray(config.images.studioImages)){config.images.studioImages=[]}
        config.images.studioImages.splice(di,1);
        renderStudioImages();
        msg('已删除（未保存）');
      }
      return;
    }
    var gotoBtn=e.target.closest?e.target.closest('[data-goto-tab]'):null;
    if(gotoBtn){showTab(gotoBtn.getAttribute('data-goto-tab'))}
  });

  /* ================= 门店空间图（images.studioImages，预约页与空间页共用） ================= */
  function renderStudioImages(){
    var host=root.querySelector('[data-studio-images]');
    if(!host)return;
    config.images=config.images||{};
    if(!Array.isArray(config.images.studioImages)){config.images.studioImages=[]}
    var list=config.images.studioImages;
    host.innerHTML=list.length?list.map(function(v,idx){
      return '<div class="item"><div class="item-head"><b>门店空间图 '+(idx+1)+'</b><span class="ops"><button type="button" class="del" data-del-studio-img="'+idx+'">删除</button></span></div>'+imageBlockHtml('images.studioImages.'+idx,v)+'</div>';
    }).join(''):'<p class="hint" style="margin:0 0 10px">暂未添加，门店卡片会先用「空间光影主图」兜底</p>';
    host.querySelectorAll('[data-path]').forEach(function(f){
      f.addEventListener('input',function(){try{syncField(f)}catch(e){msg(e.message)}});
    });
  }
  renderStudioImages();
  root.querySelectorAll('[data-add-studio-img]').forEach(function(btn){
    btn.addEventListener('click',function(){
      config.images=config.images||{};
      if(!Array.isArray(config.images.studioImages)){config.images.studioImages=[]}
      config.images.studioImages.push('');
      renderStudioImages();
      msg('已添加，上传或填写图片地址后保存');
    });
  });

  /* ================= 全站主题（前台网站配色） ================= */
  (function(){
    var cards=document.querySelectorAll('[data-site-theme-set]');
    if(!cards.length)return;
    function applySiteTheme(id){
      setPath('site.theme',id);
      cards.forEach(function(c){
        c.classList.toggle('active',c.getAttribute('data-site-theme-set')===id);
      });
    }
    applySiteTheme(String(getPath('site.theme')||'ebony-ivory'));
    cards.forEach(function(c){
      c.addEventListener('click',function(){
        applySiteTheme(c.getAttribute('data-site-theme-set'));
        msg('网站配色已选「'+c.querySelector('b').textContent+'」，点「保存全部更改」后前台生效');
      });
    });
  })();

  /* ================= 后台外观主题 ================= */
  var THEME_KEY='yimai_admin_theme';
  function applyAdminTheme(id){
    if(id){document.body.setAttribute('data-admin-theme',id)}
    else{document.body.removeAttribute('data-admin-theme')}
    try{localStorage.setItem(THEME_KEY,id)}catch(e){}
    setPath('site.adminTheme',id||'');
    document.querySelectorAll('[data-admin-theme-set]').forEach(function(c){
      c.classList.toggle('active',c.getAttribute('data-admin-theme-set')===id);
    });
  }
  (function(){
    var saved=document.body.getAttribute('data-admin-theme');
    if(!saved){try{saved=localStorage.getItem(THEME_KEY)}catch(e){}}
    applyAdminTheme(saved||'paper');
    document.querySelectorAll('[data-admin-theme-set]').forEach(function(c){
      c.addEventListener('click',function(){
        applyAdminTheme(c.getAttribute('data-admin-theme-set'));
        msg('外观已切换，本浏览器即时生效；点「保存全部更改」后所有设备都用这款');
      });
    });
  })();

  /* ================= 图片库（本地上传过的图片，已同步图床的优先图床地址） ================= */
  var LIB={data:null,target:null};
  function localImageSrc(path){return (window.YIMAI_URI||'')+'/assets/images'+path}
  function openLibrary(path){
    LIB.target=path;
    var modal=document.querySelector('[data-library]');
    if(!modal)return;
    modal.classList.add('open');
    if(LIB.data){renderLibrary()}
    else{
      var grid=modal.querySelector('[data-library-grid]');
      grid.innerHTML='<p class="hint" style="padding:20px">正在加载图片库…</p>';
      fetch('/admin/library',{headers:{'X-CSRF-TOKEN':window.CSRF_TOKEN||''}})
        .then(function(r){return r.json()})
        .then(function(d){LIB.data=(d&&d.items)||[];renderLibrary()})
        .catch(function(){grid.innerHTML='<p class="hint" style="padding:20px">加载失败，请关闭后重试</p>'});
    }
    setTimeout(function(){var s=modal.querySelector('[data-library-search]');if(s){s.focus()}},120);
  }
  function closeLibrary(){
    var modal=document.querySelector('[data-library]');
    if(modal){modal.classList.remove('open')}
  }
  function renderLibrary(){
    var modal=document.querySelector('[data-library]');
    if(!modal||!LIB.data)return;
    var grid=modal.querySelector('[data-library-grid]');
    var status=modal.querySelector('[data-library-status]');
    var q=(modal.querySelector('[data-library-search]').value||'').trim().toLowerCase();
    var list=LIB.data.filter(function(it){
      return !q||it.path.toLowerCase().indexOf(q)>=0;
    });
    status.textContent='共 '+list.length+' 张'+(q?'（已筛选）':'');
    if(!list.length){
      grid.innerHTML='<p class="hint" style="padding:20px">没有匹配的图片。图片库收录后台上传过的全部图片，新上传的图片保存页面后即可在这里选到。</p>';
      return;
    }
    grid.innerHTML=list.map(function(it){
      var idx=LIB.data.indexOf(it);
      var src=it.imgbed||localImageSrc(it.path);
      var fb=it.imgbed?localImageSrc(it.path):'';
      return '<button type="button" class="lib-item" data-lib-idx="'+idx+'" title="'+esc(it.path.replace('/uploads/',''))+'">'
        +'<span class="lib-thumb"><img src="'+esc(src)+'" data-fb="'+esc(fb)+'" loading="lazy"></span>'
        +'<span class="lib-name">'+esc(it.path.replace('/uploads/',''))+'</span>'
        +(it.imgbed?'<em class="lib-badge">图床</em>':'')
        +'</button>';
    }).join('');
  }
  function chooseLibraryImage(idx){
    var it=LIB.data&&LIB.data[idx];
    if(!it||!LIB.target)return;
    var side=currentSide(LIB.target);
    var value=(side==='imgbed'&&it.imgbed)?it.imgbed:it.path;
    var field=root.querySelector('[data-path="'+LIB.target+'"]');
    if(field){field.value=value}
    setPath(LIB.target,value);
    updatePreview(LIB.target);
    refreshToggle(LIB.target);
    closeLibrary();
    msg('已选择 '+it.path.replace('/uploads/','')+'（点保存生效）');
  }
  /* 弹窗在表单外，选择/关闭事件绑在 document 上 */
  document.addEventListener('click',function(e){
    var item=e.target.closest?e.target.closest('[data-lib-idx]'):null;
    if(item){chooseLibraryImage(parseInt(item.getAttribute('data-lib-idx'),10));return}
    if(e.target.closest&&e.target.closest('[data-library-close]')){closeLibrary();return}
    var backdrop=e.target.closest?e.target.closest('[data-library]'):null;
    if(backdrop&&e.target===backdrop){closeLibrary()}
  });
  var libSearch=document.querySelector('[data-library-search]');
  if(libSearch){libSearch.addEventListener('input',renderLibrary)}
  /* 库内缩略图加载失败 → 换本地地址 */
  document.addEventListener('error',function(e){
    var el=e.target;
    if(!el||!el.matches||!el.matches('.lib-thumb img'))return;
    var fb=el.getAttribute('data-fb');
    if(fb){el.removeAttribute('data-fb');el.src=fb}
  },true);

  /* ================= 保存 ================= */
  var saveBtn=root.querySelector('button[type=submit]');
  root.addEventListener('submit',function(event){
    event.preventDefault();
    try{syncAll()}catch(e){msg(e.message);return}
    var fd=new FormData(root);
    msg('保存中...');
    fetch('/admin/save',{method:'POST',body:fd}).then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})}).then(function(data){
      msg((data.message||'已保存')+' ✓ 已写入，前台刷新（Ctrl+F5）可见');
      saveBtn.classList.add('done');
    }).catch(function(error){msg(error.message||'保存失败')});
  });

  /* ================= 重置 ================= */
  var resetBtn=root.querySelector('[data-reset]');
  if(resetBtn){
    resetBtn.addEventListener('click',function(){
      if(confirm('重置为服务器当前配置？未保存的更改会丢失。')){
        location.reload();
      }
    });
  }

  /* ================= 改密码 ================= */
  var changeBtn=root.querySelector('[data-change-password]');
  if(changeBtn){
    changeBtn.addEventListener('click',function(){
      var oldPwd=root.querySelector('#old_password');
      var newPwd=root.querySelector('#new_password');
      var pmsg=root.querySelector('[data-password-msg]');
      if(!oldPwd||!newPwd||!pmsg){return}
      var fd=new FormData();
      fd.append('csrf_token',window.CSRF_TOKEN||'');
      fd.append('old_password',oldPwd.value);
      fd.append('new_password',newPwd.value);
      pmsg.textContent='修改中...';
      fetch('/admin/changepass',{method:'POST',body:fd}).then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})}).then(function(data){pmsg.textContent=data.message||'已修改';oldPwd.value='';newPwd.value=''}).catch(function(error){pmsg.textContent=error.message||'修改失败'});
    });
  }

  /* ================= Logo 大小滑块实时预览 ================= */
  var logoLive = root.querySelector('[data-logo-live]');
  var logoPath = config.site && config.site.logo ? config.site.logo : '';
  if (logoLive && logoPath) {
    var src = (String(logoPath).indexOf('http')===0) ? logoPath : (window.YIMAI_URI||'') + '/assets/images' + logoPath;
    logoLive.src = src;
  }
  function syncLogoHeight(){
    var v = parseInt(config.site ? (config.site.logoHeight||13) : 13, 10);
    if (isNaN(v) || v < 4) v = 4;
    if (v > 60) v = 60;
    if (logoLive) logoLive.style.height = v + 'px';
    root.querySelectorAll('[data-path="site.logoHeight"]').forEach(function(el){
      var target = String(el.value);
      if (target !== String(v)) el.value = v;
    });
  }
  root.querySelectorAll('[data-path="site.logoHeight"]').forEach(function(el){
    el.addEventListener('input', function(){
      var v = parseInt(el.value, 10);
      if (!isNaN(v)) {
        config.site = config.site || {};
        config.site.logoHeight = v;
        syncLogoHeight();
      }
    });
  });
  syncLogoHeight();

  // 初始化图片预览
  Object.keys(window.CONFIG||{}).forEach(function(sec){
    if(sec==='site'){['logo','favicon','wechatQr'].forEach(function(k){updatePreview('site.'+k)})}
    if(sec==='images'){Object.keys(config.images||{}).forEach(function(k){updatePreview('images.'+k)})}
  });
})();
