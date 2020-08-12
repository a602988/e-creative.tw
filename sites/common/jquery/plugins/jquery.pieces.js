$.fn.oldGetHeightFunction=$.fn.height;$.fn.getHeight=function(){if(this.is($(window)))
return(this.oldGetHeightFunction()<this[0].innerHeight)?this.oldGetHeightFunction():this[0].innerHeight
else
return this.oldGetHeightFunction();};(function($,window,document,undefined){var pluginName="pieces";var options;var defaults={responsive:true,masked:false,cols:10,rows:10,onStart:undefined,onMouseOver:undefined,onMouseOut:undefined,onClick:undefined,onVisible:undefined,animations:[],onVisibleFired:false};function Plugin(element,options){this.name=pluginName;this.defaults=defaults;this.options=$.extend({},defaults,options);this.element=element;this.j_element=$(element);this.j_parent=$(element).parent();this.timeline=new TimelineMax();$.extend(this.options.animations,pieces_animations);this._waitForLoad();}
Plugin.prototype={_waitForLoad:function()
{var plugin=this;var src=plugin.j_element.attr("src");plugin.j_element.attr("src","");plugin.j_element.one('load',function(){plugin._init();});plugin.j_element.attr("src",src);},_init:function()
{var plugin=this;if(plugin.options.onMouseOver)
plugin.j_parent.on('mouseenter',{plugin:this},function(){plugin._onCall(plugin.options.onMouseOver);});if(plugin.options.onMouseOut)
plugin.j_parent.on('mouseleave',{plugin:this},function(){plugin._onCall(plugin.options.onMouseOut);});if(plugin.options.onClick)
plugin.j_parent.on('click',{plugin:this},function(){plugin._onCall(plugin.options.onClick);});plugin._createPieces();if(plugin.options.onVisible){$(window).load(function(){$(window).scroll(function(){plugin._fireOnVisible();});plugin._fireOnVisible();});}
if(plugin.options.onStart)
plugin._onCall(plugin.options.onStart);},_onCall:function(params)
{if(params===undefined)
return false;var plugin=this;plugin._animate(params);},_fireOnVisible:function()
{var plugin=this;if(plugin.onVisibleFired)
return false;if(plugin._isScrolledIntoView())
{plugin.onVisibleFired=true;plugin._onCall(plugin.options.onVisible);}},_isScrolledIntoView:function()
{var plugin=this;var threshold=0;if(typeof plugin.options.onVisible!=='undefined')
if(typeof plugin.options.onVisible.threshold!=='undefined')
threshold=plugin.options.onVisible.threshold;var scroll_top=$(window).scrollTop();var window_height=$(window).getHeight();var img_top=plugin.j_container.offset().top;var img_height=plugin.j_container.height();return((scroll_top+threshold<img_top+img_height)&&scroll_top+window_height>img_top+threshold);},_animate:function(params)
{var plugin=this;if((typeof params.overwrite==='undefined'||!params.overwrite)&&plugin.animating)
return false;plugin.animating=true;var animation=$.extend(true,{},params);if($.isArray(animation.animation))
plugin._getRandomAnimation(animation);else
plugin._getAnimation(animation);plugin._getAnimationInfo(animation);plugin._startAnimation(animation);},_getAnimation:function(s)
{if(typeof s==='undefined')
return;var plugin=this;if(s.animation=="in"){s.animation=new Array();for(var j=0;j<plugin.options.animations.length;j++)
if(plugin.options.animations[j]["type"]=="in")
s.animation.push(plugin.options.animations[j]["name"]);plugin._getRandomAnimation(s);return false;}
else if(s.animation=="mid"){s.animation=new Array();for(var j=0;j<plugin.options.animations.length;j++)
if(plugin.options.animations[j]["type"]=="mid")
s.animation.push(plugin.options.animations[j]["name"]);plugin._getRandomAnimation(s);return false;}
else if(s.animation=="out"){s.animation=new Array();for(var j=0;j<plugin.options.animations.length;j++)
if(plugin.options.animations[j]["type"]=="out")
s.animation.push(plugin.options.animations[j]["name"]);plugin._getRandomAnimation(s);return false;}
if(s.animation!="")
{var found=false;for(var j=0;j<plugin.options.animations.length;j++)
{if(s.animation==plugin.options.animations[j].name)
{found=true;s.steps=jQuery.extend(true,[],plugin.options.animations[j].steps);}}}},_getRandomAnimation:function(s)
{var plugin=this;if($.isArray(s.animation))
{var a=Math.floor(s.animation.length*Math.random());s.name=s.animation[a];var found=false;for(var j=0;j<plugin.options.animations.length;j++)
{if(s.name==plugin.options.animations[j].name)
{found=true;s.steps=jQuery.extend(true,[],plugin.options.animations[j].steps);}}}},_isHidden:function(el)
{var plugin=this;var opacity=plugin.j_element.css("opacity");var visibility=plugin.j_element.css("visibility");return((opacity==0)||(visibility=="hidden"));},_createPieces:function()
{var plugin=this;var img_path=plugin.j_element.attr("src");var img_width=plugin.j_element.width();var img_height=plugin.j_element.height();var parent_width=this.j_parent.width();plugin.starting_width=img_width;plugin.img_ratio=img_width/img_height;plugin.img_percent=img_width/parent_width;var element_classes=plugin.j_element.attr("class");var is_hidden=plugin._isHidden(plugin.j_element);plugin.j_element.remove();var num_cols=plugin.options.cols;var num_rows=plugin.options.rows;var tile_width=Math.round(img_width/num_cols);var tile_height=Math.round(img_height/num_rows);var y_deficit=img_height-Math.round(num_rows*tile_height);var x_deficit=img_width-Math.round(num_cols*tile_width);plugin.j_container=$("<div/>",{css:{display:"inline-block",position:"relative",width:img_width,height:img_height}}).addClass('pi_container').addClass(element_classes).appendTo(plugin.j_parent);if(plugin.options.masked)
plugin.j_container.css("overflow","hidden");if(plugin.options.css!==undefined){$.each(plugin.options.css,function(property,val){plugin.j_container.css(property,val);})}
if(plugin.options["class"]!==undefined){plugin.j_container.addClass(plugin.options["class"]);}
TweenLite.set(plugin.j_container,{opacity:1,"visibility":"visible"});if(plugin.options.responsive)
$(window).resize(function(){plugin._manageResponsiveness();});var tiles='';for(var r=0;r<num_rows;r++)
{for(var c=0;c<num_cols;c++)
{var left=Math.round(c*tile_width);var top=Math.round(r*tile_height);var w=tile_width;var h=tile_height;if(r==num_rows-1)
h+=y_deficit;if(c==num_cols-1)
w+=x_deficit;tiles+='<div id="pt-'+r+'-'+c+'" class="pi_tile" style="position: absolute; overflow: hidden; left: '+left+'px; top: '+top+'px; width: '+w+'px; height: '+h+'px; opacity: '+(is_hidden?0:1)+'">';tiles+='<img src="'+img_path+'" class="pi_tile_img" style="width: '+img_width+'px; height: '+img_height+'px; position: absolute; left: '+(-left)+'px; top: '+(-top)+'px" />';tiles+='</div>';};}
plugin.j_container.html(tiles);plugin.pieces=plugin.j_container.find(".pi_tile");plugin.num_pieces=plugin.pieces.length;},_manageResponsiveness:function()
{var plugin=this;var img_width=plugin.j_parent.width()*plugin.img_percent;var img_height=img_width/plugin.img_ratio;plugin.j_container.width(img_width).height(img_height);var num_cols=plugin.options.cols;var num_rows=plugin.options.rows;var tile_width=Math.round(img_width/num_cols);var tile_height=Math.round(img_height/num_rows);var y_deficit=img_height-Math.round(num_rows*tile_height);var x_deficit=img_width-Math.round(num_cols*tile_width);for(var r=0;r<num_rows;r++)
{for(var c=0;c<num_cols;c++)
{var left=Math.round(c*tile_width);var top=Math.round(r*tile_height);var w=tile_width;var h=tile_height;if(r==num_rows-1)
h+=y_deficit;if(c==num_cols-1)
w+=x_deficit;var id="pt-"+r+"-"+c,tiles=arguments.callee.tiles||{},tile=tiles[id]||plugin.j_container.find("#"+id);tile.css({width:w,height:h,left:left,top:top});tile.find('img').css({width:img_width,height:img_height,left:-left,top:-top});};}
if(plugin.options.onVisible)
plugin._fireOnVisible();},_getAnimationInfo:function(animation)
{var plugin=this;plugin.current_animation={};plugin.current_animation.num_elements=plugin.num_pieces;plugin.current_animation.jelements=".pi_tile";for(var i=1;i<animation.steps.length;i++)
{var s=animation.steps[i];if(animation.speed!=undefined)
s.time*=100/animation.speed;if(animation.stagger!=undefined)
s.stagger*=100/animation.stagger;}
for(var i=1;i<animation.steps.length;i++)
{var step=animation.steps[i];step.total_time=(plugin.current_animation.num_elements-1)*parseFloat(step.stagger)+parseFloat(step.time);}
var delay=0;animation.steps[1].delay=0;for(var i=2;i<animation.steps.length;i++)
{delay+=animation.steps[i-1].total_time*animation.steps[i].start_at;animation.steps[i].delay=delay;}
plugin.current_animation.total_time=0;for(var i=1;i<animation.steps.length;i++)
if(animation.steps[i].total_time+animation.steps[i].delay>plugin.current_animation.total_time)
plugin.current_animation.total_time=animation.steps[i].total_time+animation.steps[i].delay;plugin.current_animation.one_element_total_time=0;for(var i=1;i<animation.steps.length;i++)
if(animation.steps[i].time+animation.steps[i].delay>plugin.current_animation.one_element_total_time)
plugin.current_animation.one_element_total_time=animation.steps[i].time+animation.steps[i].delay;},_startAnimation:function(animation)
{var plugin=this;plugin.animating=true;var s=animation.steps[0];if(animation.onStart!==undefined)
{if(animation.delay!==undefined)
TweenMax.delayedCall(animation.delay,function(){animation.onStart()});else
animation.onStart();}
TweenLite.set(plugin.j_container,{perspective:s.p});plugin.timeline.clear();plugin.timeline=new TimelineMax();plugin.timeline.pause();plugin.timeline.eventCallback("onComplete",function(){plugin._doSteps(animation);});var tweens=Array();$.each(plugin.pieces,function(idx,val){if(s.xt=="value")
var value_x=s.x;else if(s.xt=="random")
var value_x=Math.floor(s.x1+(s.x2-s.x1)*Math.random());if(s.yt=="value")
var value_y=s.y;else if(s.yt=="random")
var value_y=Math.floor(s.y1+(s.y2-s.y1)*Math.random());var opacity=s.o;if(animation.startingOpacity!==undefined)
opacity=animation.startingOpacity;tweens.push(TweenMax.to(this,0,{opacity:opacity,transformOrigin:s.tox+"% "+s.toy+"% "+s.toz+"px",x:value_x,y:value_y,z:0.0000000001,rotationX:s.rx,rotationY:s.ry,rotationZ:s.rz,scaleX:s.sx,scaleY:s.sy,delay:(animation.delay===undefined)?0:animation.delay}));});plugin.timeline.add(tweens);plugin.timeline.play();},_doSteps:function(animation)
{var plugin=this;plugin.timeline.clear();plugin.timeline=new TimelineMax();plugin.timeline.pause();plugin.timeline.eventCallback("onComplete",function(){plugin.animating=false;if(animation.onComplete!==undefined)
animation.onComplete();});var elems=plugin.pieces;var indexes=Array();if(animation.order=="reverse"){for(var e=0;e<plugin.current_animation.num_elements;e++)
indexes[e]=plugin.current_animation.num_elements-e-1;}
else if(animation.order=="random"){for(var e=0;e<plugin.current_animation.num_elements;e++)
indexes[e]=e;for(var e=0;e<plugin.current_animation.num_elements;e++)
{var x=Math.floor(Math.random()*plugin.current_animation.num_elements);var tmp=indexes[e];indexes[e]=indexes[x];indexes[x]=tmp;}}
else{for(var e=0;e<plugin.current_animation.num_elements;e++)
indexes[e]=e;}
for(var i=1;i<animation.steps.length;i++)
{var s=animation.steps[i];var s_prev=animation.steps[i-1];TweenLite.set(plugin.j_container,{perspective:s.p});var values={};if(s.o!=s_prev.o)
values.opacity=s.o;if(s.tox!=s_prev.tox||s.toy!=s_prev.toy||s.toz!=s_prev.toz)
values.transformOrigin=s.tox+"% "+s.toy+"% "+s.toz+"px";if(s.rx!=s_prev.rx)
values.rotationX=s.rx;if(s.ry!=s_prev.ry)
values.rotationY=s.ry;if(s.rz!=s_prev.rz)
values.rotationZ=s.rz;if(s.sx!=s_prev.sx)
values.scaleX=s.sx;if(s.sy!=s_prev.sy)
values.scaleY=s.sy;values.z=0.0000000001;values.ease=s.e;for(var e=0;e<plugin.current_animation.num_elements;e++)
{if(s.xt=="random")
var x=Math.floor(s.x1+(s.x2-s.x1)*Math.random());else if(s.xt!=s_prev.type||s.x!=s_prev.x)
var x=s.x;if(s.yt=="random")
var y=Math.floor(s.y1+(s.y2-s.y1)*Math.random());else if(s.yt!=s_prev.type||s.y!=s_prev.y)
var y=s.y;plugin.timeline.add(TweenMax.to(elems[indexes[e]],s.time,$.extend({},values,{x:x,y:y})),s.delay+e*s.stagger);}}
plugin.timeline.play();}};$.fn[pluginName]=function(options)
{var args=arguments;if(options===undefined||typeof options==='object'){return this.each(function(){if(!$.data(this,'plugin_'+pluginName))
$.data(this,'plugin_'+pluginName,new Plugin(this,options));});}
else if(typeof options==='string'&&options[0]!=='_'){return this.each(function(){var instance=$.data(this,'plugin_'+pluginName);if(instance instanceof Plugin&&typeof instance[options]==='function')
instance[options].apply(instance,Array.prototype.slice.call(args,1));});}};})(jQuery,window,document);function htmlPieces()
{$.each($("body").find("[pi-start], [pi-mouseover], [pi-mouseout], [pi-click], [pi-visible]"),function(idx,val){var start=$(this).attr("pi-start");var over=$(this).attr("pi-mouseover");var out=$(this).attr("pi-mouseout");var click=$(this).attr("pi-click");var vis=$(this).attr("pi-visible");var config=$(this).attr("pi-config");if(typeof start!=='undefined')
{start=start.replace(/\s+/g,'').split(",");var start_obj={animation:start[0],speed:start[1],stagger:start[2],delay:start[3],order:start[4]}}
if(typeof over!=='undefined')
{over=over.replace(/\s+/g,'').split(",");var over_obj={animation:over[0],speed:over[1],stagger:over[2],delay:over[3],order:over[4],overwrite:(over[5]=="false"||(typeof over[5]==="undefined"))?false:true}}
if(typeof out!=='undefined')
{out=out.replace(/\s+/g,'').split(",");var out_obj={animation:out[0],speed:out[1],stagger:out[2],delay:out[3],order:out[4],overwrite:(out[5]=="false"||(typeof out[5]==="undefined"))?false:true}}
if(typeof click!=='undefined')
{click=click.replace(/\s+/g,'').split(",");var click_obj={animation:click[0],speed:click[1],stagger:click[2],delay:click[3],order:click[4],overwrite:(click[5]=="false"||(typeof click[5]==="undefined"))?false:true}}
if(typeof vis!=='undefined')
{vis=vis.replace(/\s+/g,'').split(",");var vis_obj={animation:vis[0],speed:vis[1],stagger:vis[2],delay:vis[3],order:vis[4]}}
if(typeof config!=='undefined')
{config=config.replace(/\s+/g,'').split(",");var cols=config[0];var rows=config[1];var responsive=(config[2]=="true")?true:false;var masked=(config[3]=="false"||(typeof config[3]==="undefined"))?false:true;}
$(this).pieces({cols:cols,rows:rows,responsive:responsive,masked:masked,onStart:start_obj,onMouseOver:over_obj,onMouseOut:out_obj,onClick:click_obj,onVisible:vis_obj});});};$(document).ready(function(){htmlPieces();});