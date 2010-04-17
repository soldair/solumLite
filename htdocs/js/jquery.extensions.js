/*
 * Copyright 2009-2010, Ryan Day
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
if(!window.console){
	(function(){

	/*var d = false;
	$(function(){
		d = $("<div></div>")[0];
		$(d).appendTo('body').css({width:'300px',height:'200px',position:'absolute',top:'0px',right:'0px','overflow-y':'scroll',background:'#fff',padding:'10px',zIndex:'5000'});
	});
	var log = function(msg){
		if(d){
			var l = arguments.length;
			var str = '';
			for(var i =0;i<l;i++) {
				str += arguments[i].toString().replace(/>/g,'&gt;').replace(/</g,'&lt;');
			}
			$(d).append("<div>"+str+"</div>");
		}
	};*/
	var log = function(){};
	window.console = {
		log:log,
		info:log,
		error:log,
		warn:log
	};

	}());
}

(function($){

$.fn.extend({
	flashClass:function(cls,spd){
		var spds={'fast':2000,'normal':4500,'slow':6000};
		if (/[^\d]/.test(spd)) spd = (typeof(spds[spd])!='undefined'?spds[spd]:spds['normal']);
		if(spd>400){
			this.each(function(k,v){
				var el = this;
				$(el).addClass(cls);

				var hits = (+($(el).attr('jqhits')) || 0)+1;
				$(el).attr('jqhits',hits);
				setTimeout(function(){
					if(hits == $(el).attr('jqhits')){
						$(el).removeClass(cls);
					}
				},spd);
			});
		}
		return this;
	},
	center:function(x,y){
		var dims;
		//getter mode
		if(typeof x == 'undefined'){
			dims = this.getDims();
			x = dims.x+(Math.round(dims.w/2));
			y = dims.y+(Math.round(dims.h/2));
			return {x:x,y:y};
		} else {
		//setter mode
			if(typeof x == 'object'){
			//if x is an element i want to use the center of it as my x,y
				var center = $(x).center();
				x = center.x;
				y = center.y;
			}
			dims = this.getDims();
			if(!$(this).css('position')) $(this).css('position','absolute');
			return $(this).css({top:y-Math.round(dims.h/2)+'px',left:x-Math.round(dims.w/2)+'px'});
		}
	},
	getDims:function (outer) {
		return $.positionHelper.elementDims(this, outer);
	},
	top:function(px,relative){
		if(px || px === 0) return  this.css({position:(relative?'relative':'absolute'),top:px+'px'});
		return this.getDims().y;
	},
	left:function(px,relative){
		if(px || px === 0) return this.css({position:(relative?'relative':'absolute'),left:px+'px'});
		return this.getDims().x;
	},
	fadeBox:function(html,duration,appendTo){
		duration = duration || 3000;
		var activeClass = 'fade-box-active';
		this.each(function(){
			if($(this).hasClass(activeClass)) return;

			var el = this;
			var dims = $(el).getDims();
			var left = dims.x;
			var btm = dims.y+dims.h;

			if(!appendTo){
				appendTo = $("body")[0];
			} else {
				appendTo = $(appendTo)[0];
				//make dims relative to appendto cont
				var appendToDims = $(appendTo).getDims();
				left = left-appendToDims.x;
				btm = btm-appendToDims.y;
			}

			var node = $("<div/>").appendTo(appendTo).hide().html(html).css({zIndex:1005}).top(btm).left(left).show()[0];
			$(el).addClass(activeClass);

			setTimeout(function(){
				$(node).fadeOut('fast',function(){
					$(el).removeClass(activeClass);
					$(node).remove();
				});
			},duration);
		});
		return this;
	},
	log:function(msg){
		if(msg) $.logInfo(msg);
		arguments[0] = this;
		$.log.apply(this,arguments);
		return this;
	},
	watermark:function(defaultText,color){
		if(!color) color = "#999"
;
		var toger = function(el,txt,origColor){
			$(el).blur(function(){
				if($.trim($(this).val()) === ''){
					$(this).val(txt || '').css({color:color});
				} else $(this).css({color:''});
			});
			$(el).focus(function(){if($.trim($(this).val()) == txt) $(this).val('').css({color:origColor || ''});});
		}

		this.each(function(){
			if(this.nodeName != 'INPUT') return;
			if($(this).attr('type') != 'text') return;
			var txt = defaultText;
			if(!txt) txt = $(this).attr('title');
			if(txt){
				toger(this,txt,$(this).css('color'));
				$(this).blur();
			}
		});

		return this;
	},
	defaultText:function(txt){
		$(this.selector).live('blur',function(){
			if($.trim($(this).val()||'') === '') $(this).val(txt||$(this).attr('title') || '');
		});

		$(this.selector).live('focus',function(){
			if($.trim($(this).val() || '') === (txt||$(this).attr('title'))) $(this).val("");
		});

		if(this.length) this.blur();
	}
});

$.extend({
	positionHelper:{
		drawSlagBox:function(selector,html){

			if($("#slagbox").length > 0) $("#slagbox").remove();

			var dims = this.elementDims($(selector),true);

			var pos_str = 'width:'+dims.w+'px;height:'+dims.h+'px;position:absolute;z-index:1500;top:'+dims.y+'px;left:'+dims.x+'px';

			$('body').append('<div id="slagbox" style="background:purple;'+pos_str+'">SLAG BOX<div>'+(html || '')+'</div></div>');
	
		},
		elementDims:function(jq,outer){
			var el = $(jq)[0];
			var dims = {w:0,h:0,x:0,y:0};

			if(outer){
				dims.w = $(el).outerWidth();
				if(!dims.w || dims.w == 'NaN') dims.w = false;
				dims.h = $(el).outerHeight();
				if(!dims.h || dims.h == 'NaN') dims.h = false;
			}
			if(el){
				if(!dims.w){
					dims.w = $(el).width();
					//if an image is hidden this is the only way to get the dimensions
					if(!dims.w && el.nodeName == 'IMG') dims.w = el.width;
				}
				if(!dims.h){
					dims.h = $(el).height();
					if(!dims.h && el.nodeName == 'IMG') dims.h = el.height;
				}
			} else el={};

			if(el === window){
				dims.x = jq.scrollLeft();
				dims.y = jq.scrollTop();
			} else if(el === document){
				dims.x = 0;
				dims.y = 0;
			} else {
				//IE7
				try{
					dims.x = jq.offset().left;
					dims.y = jq.offset().top;
				}catch(e){dims.x = 0;dims.y = 0;}
			}
			return dims;
		}
	},
	isPreloaded:function(src){
		return ($.preloaded[src]? true : false);
	},
	preloaded:{},
	preload:function(src,callback,error){
		if(typeof(src) == 'object' && src.length > 0){
			$.each(src,function(k,v){
				$.preload(v,callback,error);
			});
		} else {
			var img = new Image();
			$(img).bind('load',function(){
				$.preloaded[src] = {w:this.width,h:this.width};
				callback.call(img);
			});
			if( error ) $(img).bind('error',error);
			img.src = src;
		}
	},
	log:function(){
		console.log.apply(console,arguments);
	},
	logInfo:function(){
		console.info.apply(console,arguments);
	},
	logWarn:function(){
		console.warn.apply(console,arguments);
	},
	withinElement:function(child,parent) {
		// Traverse up the tree
		while ( child && child != parent )
			try { child = child.parentNode; }
			catch(e) { parent = child; }

		if( parent != child ){
			return false;
		}

		return true;
	},
	overlayCurrent:false,
	overlay:function(arg,onclose){
		arg = arg || 'create';
		var body = $("body")[0];
		if(arg == 'create'){
			if(!$.overlayCurrent){
				var docDims = $(document).getDims();
				//$(body).css({overflow:'hidden'});//remove scrollbars

				$("select").css('visibility','hidden').addClass('jq_overlay_hidden');//ie6 select boxes poke through the overlay

				$.overlayCurrent = $('<div class="document-overlay"></div>').height(docDims.h).width(docDims.w).top(0).left(0).appendTo(body)[0];
				$($.overlayCurrent).click(function(ev){
					$.overlay('remove');
					if(typeof onclose == 'function'){
						this.onclose = onclose;
						this.onclose(ev);
					}
				});
				$(window).bind('resize',function(){
					$($.overlayCurrent).width($(document).width());
					$($.overlayCurrent).height($(document).height());
				});
			}
		} else {
			$("select.jq_overlay_hidden").css('visibility','visible').removeClass('jq_overlay_hidden');
			if($.overlayCurrent){
				$(window).unbind('resize');
				$($.overlayCurrent).remove();
				//$(body).css({overflow:''});//put back scrollbars
				$.overlayCurrent = null;
			}
		}
		return $.overlayCurrent;
	}
});

if(!window.firebug){
	if(location.hash.indexOf('firebug') != -1){
		$.fireBugLite();
		setTimeout(function(){
			window.console = firebug.d.console.cmd;
			window.console('welcome to the firebug lite hack for ie!');
		},2000);
	}
}
})(jQuery);



/* MODIFIED: 26-APR-2009 ryan day
 * jQuery Form Plugin
 * version: 2.25 (08-APR-2009)
 * @requires jQuery v1.2.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
;(function($){$.fn.ajaxSubmit=function(options){if(!this.length){log('ajaxSubmit: skipping submit process - no element selected');return this;}
if(typeof options=='function')
options={success:options};var url=new String(this.attr('action')||window.location.href);url=(url.match(/^([^#]+)/)||[])[1];url=url||'';options=$.extend({url:url,type:this.attr('method')||'GET'},options||{});var veto={};this.trigger('form-pre-serialize',[this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');return this;}
if(options.beforeSerialize&&options.beforeSerialize(this,options)===false){log('ajaxSubmit: submit aborted via beforeSerialize callback');return this;}
var a=this.formToArray(options.semantic);if(options.data){options.extraData=options.data;for(var n in options.data){if(options.data[n]instanceof Array){for(var k in options.data[n])
a.push({name:n,value:options.data[n][k]});}
else
a.push({name:n,value:options.data[n]});}}
if(options.beforeSubmit&&options.beforeSubmit(a,this,options)===false){log('ajaxSubmit: submit aborted via beforeSubmit callback');return this;}
this.trigger('form-submit-validate',[a,this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-submit-validate trigger');return this;}
var q=$.param(a);if(options.type.toUpperCase()=='GET'){options.url+=(options.url.indexOf('?')>=0?'&':'?')+q;options.data=null;}
else
options.data=q;var $form=this,callbacks=[];if(options.resetForm)callbacks.push(function(){$form.resetForm();});if(options.clearForm)callbacks.push(function(){$form.clearForm();});if(!options.dataType&&options.target){var oldSuccess=options.success||function(){};callbacks.push(function(data){$(options.target).html(data).each(oldSuccess,arguments);});}
else if(options.success)
callbacks.push(options.success);options.success=function(data,status){for(var i=0,max=callbacks.length;i<max;i++)
callbacks[i].apply(options,[data,status,$form]);};var files=$('input:file',this).fieldValue();var found=false;for(var j=0;j<files.length;j++)
if(files[j])
found=true;if(options.iframe||found){if(options.closeKeepAlive)
$.get(options.closeKeepAlive,fileUpload);else
fileUpload();}
else
$.ajax(options);this.trigger('form-submit-notify',[this,options]);return this;function fileUpload(){var form=$form[0];if($(':input[name=submit]',form).length){alert('Error: Form elements must not be named "submit".');return;}
var opts=$.extend({},$.ajaxSettings,options);var s=$.extend(true,{},$.extend(true,{},$.ajaxSettings),opts);var id='jqFormIO'+(new Date().getTime());var $io=$('<iframe id="'+id+'" name="'+id+'" src="about:blank" />');var io=$io[0];$io.css({position:'absolute',top:'-1000px',left:'-1000px'});var xhr={aborted:0,responseText:null,responseXML:null,status:0,statusText:'n/a',getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){},abort:function(){this.aborted=1;$io.attr('src','about:blank');}};var g=opts.global;if(g&&!$.active++)$.event.trigger("ajaxStart");if(g)$.event.trigger("ajaxSend",[xhr,opts]);if(s.beforeSend&&s.beforeSend(xhr,s)===false){s.global&&$.active--;return;}
if(xhr.aborted)
return;var cbInvoked=0;var timedOut=0;var sub=form.clk;if(sub){var n=sub.name;if(n&&!sub.disabled){options.extraData=options.extraData||{};options.extraData[n]=sub.value;if(sub.type=="image"){options.extraData[name+'.x']=form.clk_x;options.extraData[name+'.y']=form.clk_y;}}}
setTimeout(function(){var t=$form.attr('target'),a=$form.attr('action');form.setAttribute('target',id);if(form.getAttribute('method')!='POST')
form.setAttribute('method','POST');if(form.getAttribute('action')!=opts.url)
form.setAttribute('action',opts.url);if(!options.skipEncodingOverride){$form.attr({encoding:'multipart/form-data',enctype:'multipart/form-data'});}
if(opts.timeout)
setTimeout(function(){timedOut=true;cb();},opts.timeout);var extraInputs=[];try{if(options.extraData)
for(var n in options.extraData)
extraInputs.push($('<input type="hidden" name="'+n+'" value="'+options.extraData[n]+'" />').appendTo(form)[0]);$io.appendTo('body');io.attachEvent?io.attachEvent('onload',cb):io.addEventListener('load',cb,false);form.submit();}
finally{form.setAttribute('action',a);t?form.setAttribute('target',t):$form.removeAttr('target');$(extraInputs).remove();}},10);var nullCheckFlag=0;function cb(){if(cbInvoked++)return;io.detachEvent?io.detachEvent('onload',cb):io.removeEventListener('load',cb,false);var ok=true;try{if(timedOut)throw'timeout';var data,doc;doc=io.contentWindow?io.contentWindow.document:io.contentDocument?io.contentDocument:io.document;if((doc.body==null||doc.body.innerHTML=='')&&!nullCheckFlag){nullCheckFlag=1;cbInvoked--;setTimeout(cb,100);return;}
xhr.responseText=doc.body?doc.body.innerHTML:null;xhr.responseXML=doc.XMLDocument?doc.XMLDocument:doc;xhr.getResponseHeader=function(header){var headers={'content-type':opts.dataType};return headers[header];};if(opts.dataType=='json'||opts.dataType=='script'){var ta=doc.getElementsByTagName('textarea')[0];xhr.responseText=ta?ta.value:xhr.responseText;}
else if(opts.dataType=='xml'&&!xhr.responseXML&&xhr.responseText!=null){xhr.responseXML=toXml(xhr.responseText);}
data=$.httpData(xhr,opts.dataType);}
catch(e){ok=false;$.handleError(opts,xhr,'error',e);}
if(ok){opts.success(data,'success');if(g)$.event.trigger("ajaxSuccess",[xhr,opts]);}
if(g)$.event.trigger("ajaxComplete",[xhr,opts]);if(g&&!--$.active)$.event.trigger("ajaxStop");if(opts.complete)opts.complete(xhr,ok?'success':'error');setTimeout(function(){$io.remove();xhr.responseXML=null;},100);};function toXml(s,doc){if(window.ActiveXObject){doc=new ActiveXObject('Microsoft.XMLDOM');doc.async='false';doc.loadXML(s);}
else
doc=(new DOMParser()).parseFromString(s,'text/xml');return(doc&&doc.documentElement&&doc.documentElement.tagName!='parsererror')?doc:null;};};};$.fn.ajaxForm=function(options){return this.ajaxFormUnbind().bind('submit.form-plugin',function(){$(this).ajaxSubmit(options);return false;}).each(function(){$(":submit,input:image",this).bind('click.form-plugin',function(e){var form=this.form;form.clk=this;if(this.type=='image'){if(e.offsetX!=undefined){form.clk_x=e.offsetX;form.clk_y=e.offsetY;}else if(typeof $.fn.offset=='function'){var offset=$(this).offset();form.clk_x=e.pageX-offset.left;form.clk_y=e.pageY-offset.top;}else{form.clk_x=e.pageX-this.offsetLeft;form.clk_y=e.pageY-this.offsetTop;}}
setTimeout(function(){form.clk=form.clk_x=form.clk_y=null;},10);});});};$.fn.ajaxFormUnbind=function(){this.unbind('submit.form-plugin');return this.each(function(){$(":submit,input:image",this).unbind('click.form-plugin');});};$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el)
a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});continue;}
var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)
a.push({name:n,value:v[j]});}
else if(v!==null&&typeof v!='undefined')
a.push({name:n,value:v});}
if(!semantic&&form.clk){var inputs=form.getElementsByTagName("input");for(var i=0,max=inputs.length;i<max;i++){var input=inputs[i];var n=input.name;if(n&&!input.disabled&&input.type=="image"&&form.clk==input)
a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}}
return a;};$.fn.formSerialize=function(semantic){return $.param(this.formToArray(semantic));};$.fn.fieldSerialize=function(successful){var a=[];this.each(function(){var n=this.name;if(!n)return;var v=$.fieldValue(this,successful);if(v&&v.constructor==Array){for(var i=0,max=v.length;i<max;i++)
a.push({name:n,value:v[i]});}
else if(v!==null&&typeof v!='undefined')
a.push({name:this.name,value:v});});return $.param(a);};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))
continue;v.constructor==Array?$.merge(val,v):val.push(v);}
return val;};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))
return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=op.value;if(!v)
v=(op.attributes&&op.attributes['value']&&!(op.attributes['value'].specified))?op.text:op.value;if(one)return v;a.push(v);}}
return a;}
return el.value;};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields();});};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')
this.value='';else if(t=='checkbox'||t=='radio')
this.checked=false;else if(tag=='select')
this.selectedIndex=-1;});};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))
this.reset();});};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b;});};$.fn.selected=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')
this.checked=select;else if(this.tagName.toLowerCase()=='option'){var $sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').selected(false);}
this.selected=select;}});};function log(){if($.fn.ajaxSubmit.debug&&window.console&&window.console.log)
window.console.log('[jquery.form] '+Array.prototype.join.call(arguments,''));};})(jQuery);