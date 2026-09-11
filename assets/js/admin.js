(function(){
  var root=document.querySelector('[data-admin-form]');
  if(!root){return}
  var hidden=root.querySelector('[data-config-json]');
  var message=root.querySelector('[data-admin-message]');
  if(!hidden){return}
  var config=window.CONFIG||JSON.parse(hidden.value||'{}');

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
    var ta = t==='textarea' ? '<textarea data-path="'+path+'" '+ (extra||'') +'>'+esc(v)+'</textarea>' : '<input type="'+t+'" data-path="'+path+'" value="'+esc(v)+'" '+ (extra||'') +'>';
    return '<label>'+esc(label||'')+ta+'</label>';
  }

  /* ================= Tab 切换 ================= */
  var tabs=document.querySelectorAll('[data-tab]');
  function showTab(name){
    document.querySelectorAll('[data-tab]').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-tab')===name)});
    document.querySelectorAll('[data-panel]').forEach(function(p){p.classList.toggle('active',p.getAttribute('data-panel')===name)});
  }
  tabs.forEach(function(b){b.addEventListener('click',function(){showTab(b.getAttribute('data-tab'))})});

  /* ================= 图片预览 ================= */
  function updatePreview(path){
    var box=document.querySelector('[data-preview="'+path+'"]');
    if(!box)return
    var v=getPath(path);
    if(v){
      var url=(String(v).indexOf('http')===0)?v:(window.YIMAI_URI||'')+String(v).replace(/^\//,'');
      if(window.YIMAI_URI&&String(v).indexOf('/')===0){url=window.YIMAI_URI+'/assets/images'+v}
      box.innerHTML='<img src="'+esc(url)+'" onerror="this.parentNode.textContent=\'图片加载失败\'">';
    } else {box.innerHTML='无图片'}
  }

  /* ================= 字段同步 ================= */
  function syncField(field){
    var path=field.getAttribute('data-path');
    if(!path||path==='__root__'){return}
    var value=field.value;
    if(field.hasAttribute('data-array-lines')){value=value.split('\n').map(function(i){return i.trim()}).filter(Boolean)}
    if(field.hasAttribute('data-json')){try{value=JSON.parse(value)}catch(e){throw new Error('JSON 格式错误：'+path)}}
    setPath(path,value);
    var p=path.split('.');
    if(p[0]==='site'||p[0]==='images'){updatePreview(path)}
  }
  function syncAll(){
    root.querySelectorAll('[data-path]').forEach(function(f){if(f.getAttribute('data-path')==='__root__'){return}syncField(f)});
    hidden.value=JSON.stringify(config,null,2);
  }

  /* ================= 列表编辑器（增删改） ================= */
  var SCHEMAS={
    studios:{fields:[['name','门店名称','text'],['area','面积','text'],['address','地址','text'],['phone','电话','text']]},
    memberships:{fields:[['name','方案名称','text'],['label','副标题','text'],['feature','特点说明','textarea'],['accent','标签','text']]},
    courseThemes:{fields:[['title','主题名称','text'],['type','类型','text'],['effect','功效','textarea'],['suited','适合人群','textarea'],['image','图片路径','text']]},
    classPaths:{fields:[['title','路径名称','text'],['description','说明','textarea']]},
    instructors:{fields:[['name','姓名','text'],['role','职位','text'],['years','年限','text'],['focus','擅长','text'],['image','照片路径','text'],['summary','简介','textarea']]},
    faqs:{fields:[['q','问题','text'],['a','回答','textarea']]},
    training_programs:{fields:[['name','方案名称','text'],['price','价格','text'],['label','标签','text'],['description','说明','textarea']]},
    nav_items:{fields:[['label','菜单文字','text'],['href','链接','text']]}
  };
  var TAG_FIELDS={instructors:['credentials','specialties'],classPaths:['tags'],training_programs:['points']};

  function renderList(key){
    var host=root.querySelector('[data-editor-list="'+key+'"]');
    if(!host)return
    var list=config[key]||[];
    var schema=SCHEMAS[key];
    if(!schema){return}
    host.innerHTML=list.map(function(item,idx){
      var name=item.name||item.label||item.title||item.q||('项目 '+(idx+1));
      var html='<div class="item" data-idx="'+idx+'">';
      html+='<div class="item-head"><b>'+esc(name)+'</b><button type="button" class="del" data-del-item="'+key+'" data-idx="'+idx+'">删除</button></div>';
      html+='<div class="grid2">';
      schema.fields.forEach(function(f){
        var path=key+'.'+idx+'.'+f[0];
        var v=item[f[0]];
        if(key==='faqs'){path=key+'.'+idx+'.'+(f[0]==='q'?'0':'1');v=item[f[0]==='q'?0:1]}
        if(f[2]==='textarea'){html+=fieldHtml(path,f[1],'textarea',v,'rows="3"')}
        else{html+=fieldHtml(path,f[1],'text',v)}
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
      // 图片预览（师资/课程主题）
      if(item.image){
        html+='<div style="margin-top:10px"><span style="font-size:12px;color:var(--mut)">当前图片</span><div class="img-preview" style="height:80px;max-width:220px"><img src="'+(window.YIMAI_URI?window.YIMAI_URI+'/assets/images'+item.image:item.image)+'" onerror="this.parentNode.textContent=\'无图片\'"></div></div>';
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
        config[k].splice(idx,1);
        renderList(k);
        msg('已删除（未保存）');
      });
    });
    // 绑定 tag 删除
    host.querySelectorAll('[data-tag-del]').forEach(function(x){
      x.addEventListener('click',function(){
        var p=x.getAttribute('data-tag-del').split('.');
        var k=p[0],idx=parseInt(p[1],10),tf=p.slice(2).join('.');
        var arr=config[k][idx][tf]||[];
        var t=x.getAttribute('data-tag');
        config[k][idx][tf]=arr.filter(function(i){return i!==t});
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
        if(!config[k][idx][tf]){config[k][idx][tf]=[]}
        config[k][idx][tf].push(v);
        inp.value='';
        renderList(k);
      });
    });
    // 字段输入即时同步到 config
    host.querySelectorAll('[data-path]').forEach(function(f){
      f.addEventListener('input',function(){try{syncField(f)}catch(e){msg(e.message)}});
    });
  }

  // 渲染所有列表
  Object.keys(SCHEMAS).forEach(renderList);

  // 添加按钮
  root.querySelectorAll('[data-add-item]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var key=btn.getAttribute('data-add-item');
      if(!config[key]){config[key]=[]}
      var blank={};
      SCHEMAS[key].fields.forEach(function(f){blank[f[0]]=''});
      config[key].push(blank);
      renderList(key);
      msg('已添加空白项（填完保存）');
    });
  });

  /* ================= 普通字段即时同步 ================= */
  root.querySelectorAll('[data-path]').forEach(function(field){
    if(field.getAttribute('data-path')==='__root__'){return}
    field.addEventListener('input',function(){try{syncField(field)}catch(e){msg(e.message)}});
  });

  /* ================= 上传 ================= */
  function doUpload(input,path){
    var file=input.files&&input.files[0];
    if(!file){return}
    var fd=new FormData();
    fd.append('csrf_token',window.CSRF_TOKEN||'');
    fd.append('file',file);
    fd.append('field',path||'');
    msg('上传中...');
    fetch('/admin/upload',{method:'POST',body:fd}).then(function(res){return res.json().then(function(data){if(!res.ok){throw data}return data})}).then(function(data){
      var field=root.querySelector('[data-path="'+path+'"]');
      if(field){field.value=data.path;syncField(field)}
      renderList('instructors');renderList('courseThemes');
      msg('已上传：'+data.path+'（点保存生效）');
    }).catch(function(error){msg(error.message||'上传失败')});
  }
  root.querySelectorAll('[data-upload-for]').forEach(function(input){
    input.addEventListener('change',function(){
      var path=input.getAttribute('data-upload-for');
      doUpload(input,path);
    });
  });
  root.querySelectorAll('[data-upload-trigger]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var path=btn.getAttribute('data-upload-trigger');
      var fileInput=root.querySelector('[data-upload-for="'+path+'"]');
      if(fileInput){fileInput.click()}
    });
  });

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
