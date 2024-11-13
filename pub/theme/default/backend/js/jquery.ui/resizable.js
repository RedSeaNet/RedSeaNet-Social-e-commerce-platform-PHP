
/*!
 * jQuery UI Resizable @VERSION
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/resizable/
 */
(function(a){if(typeof define==="function"&&define.amd){define(["jquery","./core","./mouse","./widget"],a)}else{a(jQuery)}}(function(a){a.widget("ui.resizable",a.ui.mouse,{version:"@VERSION",widgetEventPrefix:"resize",options:{alsoResize:false,animate:false,animateDuration:"slow",animateEasing:"swing",aspectRatio:false,autoHide:false,containment:false,ghost:false,grid:false,handles:"e,s,se",helper:false,maxHeight:null,maxWidth:null,minHeight:10,minWidth:10,zIndex:90,resize:null,start:null,stop:null},_num:function(b){return parseInt(b,10)||0},_isNumber:function(b){return !isNaN(parseInt(b,10))},_hasScroll:function(e,c){if(a(e).css("overflow")==="hidden"){return false}var b=(c&&c==="left")?"scrollLeft":"scrollTop",d=false;if(e[b]>0){return true}e[b]=1;d=(e[b]>0);e[b]=0;return d},_create:function(){var h,c,f,d,b,e=this,g=this.options;this.element.addClass("ui-resizable");a.extend(this,{_aspectRatio:!!(g.aspectRatio),aspectRatio:g.aspectRatio,originalElement:this.element,_proportionallyResizeElements:[],_helper:g.helper||g.ghost||g.animate?g.helper||"ui-resizable-helper":null});if(this.element[0].nodeName.match(/^(canvas|textarea|input|select|button|img)$/i)){this.element.wrap(a("<div class='ui-wrapper' style='overflow: hidden;'></div>").css({position:this.element.css("position"),width:this.element.outerWidth(),height:this.element.outerHeight(),top:this.element.css("top"),left:this.element.css("left")}));this.element=this.element.parent().data("ui-resizable",this.element.resizable("instance"));this.elementIsWrapper=true;this.element.css({marginLeft:this.originalElement.css("marginLeft"),marginTop:this.originalElement.css("marginTop"),marginRight:this.originalElement.css("marginRight"),marginBottom:this.originalElement.css("marginBottom")});this.originalElement.css({marginLeft:0,marginTop:0,marginRight:0,marginBottom:0});this.originalResizeStyle=this.originalElement.css("resize");this.originalElement.css("resize","none");this._proportionallyResizeElements.push(this.originalElement.css({position:"static",zoom:1,display:"block"}));this.originalElement.css({margin:this.originalElement.css("margin")});this._proportionallyResize()}this.handles=g.handles||(!a(".ui-resizable-handle",this.element).length?"e,s,se":{n:".ui-resizable-n",e:".ui-resizable-e",s:".ui-resizable-s",w:".ui-resizable-w",se:".ui-resizable-se",sw:".ui-resizable-sw",ne:".ui-resizable-ne",nw:".ui-resizable-nw"});this._handles=a();if(this.handles.constructor===String){if(this.handles==="all"){this.handles="n,e,s,w,se,sw,ne,nw"}h=this.handles.split(",");this.handles={};for(c=0;c<h.length;c++){f=a.trim(h[c]);b="ui-resizable-"+f;d=a("<div class='ui-resizable-handle "+b+"'></div>");d.css({zIndex:g.zIndex});if("se"===f){d.addClass("ui-icon ui-icon-gripsmall-diagonal-se")}this.handles[f]=".ui-resizable-"+f;this.element.append(d)}}this._renderAxis=function(n){var k,l,j,m;n=n||this.element;for(k in this.handles){if(this.handles[k].constructor===String){this.handles[k]=this.element.children(this.handles[k]).first().show()}else{if(this.handles[k].jquery||this.handles[k].nodeType){this.handles[k]=a(this.handles[k]);this._on(this.handles[k],{mousedown:e._mouseDown})}}if(this.elementIsWrapper&&this.originalElement[0].nodeName.match(/^(textarea|input|select|button)$/i)){l=a(this.handles[k],this.element);m=/sw|ne|nw|se|n|s/.test(k)?l.outerHeight():l.outerWidth();j=["padding",/ne|nw|n/.test(k)?"Top":/se|sw|s/.test(k)?"Bottom":/^e$/.test(k)?"Right":"Left"].join("");n.css(j,m);this._proportionallyResize()}this._handles=this._handles.add(this.handles[k])}};this._renderAxis(this.element);this._handles=this._handles.add(this.element.find(".ui-resizable-handle"));this._handles.disableSelection();this._handles.mouseover(function(){if(!e.resizing){if(this.className){d=this.className.match(/ui-resizable-(se|sw|ne|nw|n|e|s|w)/i)}e.axis=d&&d[1]?d[1]:"se"}});if(g.autoHide){this._handles.hide();a(this.element).addClass("ui-resizable-autohide").mouseenter(function(){if(g.disabled){return}a(this).removeClass("ui-resizable-autohide");e._handles.show()}).mouseleave(function(){if(g.disabled){return}if(!e.resizing){a(this).addClass("ui-resizable-autohide");e._handles.hide()}})}this._mouseInit()},_destroy:function(){this._mouseDestroy();var c,b=function(d){a(d).removeClass("ui-resizable ui-resizable-disabled ui-resizable-resizing").removeData("resizable").removeData("ui-resizable").unbind(".resizable").find(".ui-resizable-handle").remove()};if(this.elementIsWrapper){b(this.element);c=this.element;this.originalElement.css({position:c.css("position"),width:c.outerWidth(),height:c.outerHeight(),top:c.css("top"),left:c.css("left")}).insertAfter(c);c.remove()}this.originalElement.css("resize",this.originalResizeStyle);b(this.originalElement);return this},_mouseCapture:function(d){var c,e,b=false;for(c in this.handles){e=a(this.handles[c])[0];if(e===d.target||a.contains(e,d.target)){b=true}}return !this.options.disabled&&b},_mouseStart:function(c){var g,d,f,e=this.options,b=this.element;this.resizing=true;this._renderProxy();g=this._num(this.helper.css("left"));d=this._num(this.helper.css("top"));if(e.containment){g+=a(e.containment).scrollLeft()||0;d+=a(e.containment).scrollTop()||0}this.offset=this.helper.offset();this.position={left:g,top:d};this.size=this._helper?{width:this.helper.width(),height:this.helper.height()}:{width:b.width(),height:b.height()};this.originalSize=this._helper?{width:b.outerWidth(),height:b.outerHeight()}:{width:b.width(),height:b.height()};this.sizeDiff={width:b.outerWidth()-b.width(),height:b.outerHeight()-b.height()};this.originalPosition={left:g,top:d};this.originalMousePosition={left:c.pageX,top:c.pageY};this.aspectRatio=(typeof e.aspectRatio==="number")?e.aspectRatio:((this.originalSize.width/this.originalSize.height)||1);f=a(".ui-resizable-"+this.axis).css("cursor");a("body").css("cursor",f==="auto"?this.axis+"-resize":f);b.addClass("ui-resizable-resizing");this._propagate("start",c);return true},_mouseDrag:function(g){var h,f,i=this.originalMousePosition,c=this.axis,d=(g.pageX-i.left)||0,b=(g.pageY-i.top)||0,e=this._change[c];this._updatePrevProperties();if(!e){return false}h=e.apply(this,[g,d,b]);this._updateVirtualBoundaries(g.shiftKey);if(this._aspectRatio||g.shiftKey){h=this._updateRatio(h,g)}h=this._respectSize(h,g);this._updateCache(h);this._propagate("resize",g);f=this._applyChanges();if(!this._helper&&this._proportionallyResizeElements.length){this._proportionallyResize()}if(!a.isEmptyObject(f)){this._updatePrevProperties();this._trigger("resize",g,this.ui());this._applyChanges()}return false},_mouseStop:function(e){this.resizing=false;var d,b,c,h,k,g,j,f=this.options,i=this;if(this._helper){d=this._proportionallyResizeElements;b=d.length&&(/textarea/i).test(d[0].nodeName);c=b&&this._hasScroll(d[0],"left")?0:i.sizeDiff.height;h=b?0:i.sizeDiff.width;k={width:(i.helper.width()-h),height:(i.helper.height()-c)};g=(parseInt(i.element.css("left"),10)+(i.position.left-i.originalPosition.left))||null;j=(parseInt(i.element.css("top"),10)+(i.position.top-i.originalPosition.top))||null;if(!f.animate){this.element.css(a.extend(k,{top:j,left:g}))}i.helper.height(i.size.height);i.helper.width(i.size.width);if(this._helper&&!f.animate){this._proportionallyResize()}}a("body").css("cursor","auto");this.element.removeClass("ui-resizable-resizing");this._propagate("stop",e);if(this._helper){this.helper.remove()}return false},_updatePrevProperties:function(){this.prevPosition={top:this.position.top,left:this.position.left};this.prevSize={width:this.size.width,height:this.size.height}},_applyChanges:function(){var b={};if(this.position.top!==this.prevPosition.top){b.top=this.position.top+"px"}if(this.position.left!==this.prevPosition.left){b.left=this.position.left+"px"}if(this.size.width!==this.prevSize.width){b.width=this.size.width+"px"}if(this.size.height!==this.prevSize.height){b.height=this.size.height+"px"}this.helper.css(b);return b},_updateVirtualBoundaries:function(e){var g,f,d,i,c,h=this.options;c={minWidth:this._isNumber(h.minWidth)?h.minWidth:0,maxWidth:this._isNumber(h.maxWidth)?h.maxWidth:Infinity,minHeight:this._isNumber(h.minHeight)?h.minHeight:0,maxHeight:this._isNumber(h.maxHeight)?h.maxHeight:Infinity};if(this._aspectRatio||e){g=c.minHeight*this.aspectRatio;d=c.minWidth/this.aspectRatio;f=c.maxHeight*this.aspectRatio;i=c.maxWidth/this.aspectRatio;if(g>c.minWidth){c.minWidth=g}if(d>c.minHeight){c.minHeight=d}if(f<c.maxWidth){c.maxWidth=f}if(i<c.maxHeight){c.maxHeight=i}}this._vBoundaries=c},_updateCache:function(b){this.offset=this.helper.offset();if(this._isNumber(b.left)){this.position.left=b.left}if(this._isNumber(b.top)){this.position.top=b.top}if(this._isNumber(b.height)){this.size.height=b.height}if(this._isNumber(b.width)){this.size.width=b.width}},_updateRatio:function(d){var e=this.position,c=this.size,b=this.axis;if(this._isNumber(d.height)){d.width=(d.height*this.aspectRatio)}else{if(this._isNumber(d.width)){d.height=(d.width/this.aspectRatio)}}if(b==="sw"){d.left=e.left+(c.width-d.width);d.top=null}if(b==="nw"){d.top=e.top+(c.height-d.height);d.left=e.left+(c.width-d.width)}return d},_respectSize:function(g){var d=this._vBoundaries,j=this.axis,l=this._isNumber(g.width)&&d.maxWidth&&(d.maxWidth<g.width),h=this._isNumber(g.height)&&d.maxHeight&&(d.maxHeight<g.height),e=this._isNumber(g.width)&&d.minWidth&&(d.minWidth>g.width),k=this._isNumber(g.height)&&d.minHeight&&(d.minHeight>g.height),c=this.originalPosition.left+this.originalSize.width,i=this.position.top+this.size.height,f=/sw|nw|w/.test(j),b=/nw|ne|n/.test(j);if(e){g.width=d.minWidth}if(k){g.height=d.minHeight}if(l){g.width=d.maxWidth}if(h){g.height=d.maxHeight}if(e&&f){g.left=c-d.minWidth}if(l&&f){g.left=c-d.maxWidth}if(k&&b){g.top=i-d.minHeight}if(h&&b){g.top=i-d.maxHeight}if(!g.width&&!g.height&&!g.left&&g.top){g.top=null}else{if(!g.width&&!g.height&&!g.top&&g.left){g.left=null}}return g},_getPaddingPlusBorderDimensions:function(d){var c=0,e=[],f=[d.css("borderTopWidth"),d.css("borderRightWidth"),d.css("borderBottomWidth"),d.css("borderLeftWidth")],b=[d.css("paddingTop"),d.css("paddingRight"),d.css("paddingBottom"),d.css("paddingLeft")];for(;c<4;c++){e[c]=(parseInt(f[c],10)||0);e[c]+=(parseInt(b[c],10)||0)}return{height:e[0]+e[2],width:e[1]+e[3]}},_proportionallyResize:function(){if(!this._proportionallyResizeElements.length){return}var d,c=0,b=this.helper||this.element;for(;c<this._proportionallyResizeElements.length;c++){d=this._proportionallyResizeElements[c];if(!this.outerDimensions){this.outerDimensions=this._getPaddingPlusBorderDimensions(d)}d.css({height:(b.height()-this.outerDimensions.height)||0,width:(b.width()-this.outerDimensions.width)||0})}},_renderProxy:function(){var b=this.element,c=this.options;this.elementOffset=b.offset();if(this._helper){this.helper=this.helper||a("<div style='overflow:hidden;'></div>");this.helper.addClass(this._helper).css({width:this.element.outerWidth()-1,height:this.element.outerHeight()-1,position:"absolute",left:this.elementOffset.left+"px",top:this.elementOffset.top+"px",zIndex:++c.zIndex});this.helper.appendTo("body").disableSelection()}else{this.helper=this.element}},_change:{e:function(c,b){return{width:this.originalSize.width+b}},w:function(d,b){var c=this.originalSize,e=this.originalPosition;return{left:e.left+b,width:c.width-b}},n:function(e,c,b){var d=this.originalSize,f=this.originalPosition;return{top:f.top+b,height:d.height-b}},s:function(d,c,b){return{height:this.originalSize.height+b}},se:function(d,c,b){return a.extend(this._change.s.apply(this,arguments),this._change.e.apply(this,[d,c,b]))},sw:function(d,c,b){return a.extend(this._change.s.apply(this,arguments),this._change.w.apply(this,[d,c,b]))},ne:function(d,c,b){return a.extend(this._change.n.apply(this,arguments),this._change.e.apply(this,[d,c,b]))},nw:function(d,c,b){return a.extend(this._change.n.apply(this,arguments),this._change.w.apply(this,[d,c,b]))}},_propagate:function(c,b){a.ui.plugin.call(this,c,[b,this.ui()]);(c!=="resize"&&this._trigger(c,b,this.ui()))},plugins:{},ui:function(){return{originalElement:this.originalElement,element:this.element,helper:this.helper,position:this.position,size:this.size,originalSize:this.originalSize,originalPosition:this.originalPosition}}});a.ui.plugin.add("resizable","animate",{stop:function(e){var j=a(this).resizable("instance"),g=j.options,d=j._proportionallyResizeElements,b=d.length&&(/textarea/i).test(d[0].nodeName),c=b&&j._hasScroll(d[0],"left")?0:j.sizeDiff.height,i=b?0:j.sizeDiff.width,f={width:(j.size.width-i),height:(j.size.height-c)},h=(parseInt(j.element.css("left"),10)+(j.position.left-j.originalPosition.left))||null,k=(parseInt(j.element.css("top"),10)+(j.position.top-j.originalPosition.top))||null;j.element.animate(a.extend(f,k&&h?{top:k,left:h}:{}),{duration:g.animateDuration,easing:g.animateEasing,step:function(){var l={width:parseInt(j.element.css("width"),10),height:parseInt(j.element.css("height"),10),top:parseInt(j.element.css("top"),10),left:parseInt(j.element.css("left"),10)};if(d&&d.length){a(d[0]).css({width:l.width,height:l.height})}j._updateCache(l);j._propagate("resize",e)}})}});a.ui.plugin.add("resizable","containment",{start:function(){var j,d,l,b,i,e,m,k=a(this).resizable("instance"),h=k.options,g=k.element,c=h.containment,f=(c instanceof a)?c.get(0):(/parent/.test(c))?g.parent().get(0):c;if(!f){return}k.containerElement=a(f);if(/document/.test(c)||c===document){k.containerOffset={left:0,top:0};k.containerPosition={left:0,top:0};k.parentData={element:a(document),left:0,top:0,width:a(document).width(),height:a(document).height()||document.body.parentNode.scrollHeight}}else{j=a(f);d=[];a(["Top","Right","Left","Bottom"]).each(function(o,n){d[o]=k._num(j.css("padding"+n))});k.containerOffset=j.offset();k.containerPosition=j.position();k.containerSize={height:(j.innerHeight()-d[3]),width:(j.innerWidth()-d[1])};l=k.containerOffset;b=k.containerSize.height;i=k.containerSize.width;e=(k._hasScroll(f,"left")?f.scrollWidth:i);m=(k._hasScroll(f)?f.scrollHeight:b);k.parentData={element:f,left:l.left,top:l.top,width:e,height:m}}},resize:function(c){var i,n,h,f,j=a(this).resizable("instance"),e=j.options,l=j.containerOffset,k=j.position,m=j._aspectRatio||c.shiftKey,b={top:0,left:0},d=j.containerElement,g=true;if(d[0]!==document&&(/static/).test(d.css("position"))){b=l}if(k.left<(j._helper?l.left:0)){j.size.width=j.size.width+(j._helper?(j.position.left-l.left):(j.position.left-b.left));if(m){j.size.height=j.size.width/j.aspectRatio;g=false}j.position.left=e.helper?l.left:0}if(k.top<(j._helper?l.top:0)){j.size.height=j.size.height+(j._helper?(j.position.top-l.top):j.position.top);if(m){j.size.width=j.size.height*j.aspectRatio;g=false}j.position.top=j._helper?l.top:0}h=j.containerElement.get(0)===j.element.parent().get(0);f=/relative|absolute/.test(j.containerElement.css("position"));if(h&&f){j.offset.left=j.parentData.left+j.position.left;j.offset.top=j.parentData.top+j.position.top}else{j.offset.left=j.element.offset().left;j.offset.top=j.element.offset().top}i=Math.abs(j.sizeDiff.width+(j._helper?j.offset.left-b.left:(j.offset.left-l.left)));n=Math.abs(j.sizeDiff.height+(j._helper?j.offset.top-b.top:(j.offset.top-l.top)));if(i+j.size.width>=j.parentData.width){j.size.width=j.parentData.width-i;if(m){j.size.height=j.size.width/j.aspectRatio;g=false}}if(n+j.size.height>=j.parentData.height){j.size.height=j.parentData.height-n;if(m){j.size.width=j.size.height*j.aspectRatio;g=false}}if(!g){j.position.left=j.prevPosition.left;j.position.top=j.prevPosition.top;j.size.width=j.prevSize.width;j.size.height=j.prevSize.height}},stop:function(){var g=a(this).resizable("instance"),c=g.options,i=g.containerOffset,b=g.containerPosition,d=g.containerElement,e=a(g.helper),k=e.offset(),j=e.outerWidth()-g.sizeDiff.width,f=e.outerHeight()-g.sizeDiff.height;if(g._helper&&!c.animate&&(/relative/).test(d.css("position"))){a(this).css({left:k.left-b.left-i.left,width:j,height:f})}if(g._helper&&!c.animate&&(/static/).test(d.css("position"))){a(this).css({left:k.left-b.left-i.left,width:j,height:f})}}});a.ui.plugin.add("resizable","alsoResize",{start:function(){var b=a(this).resizable("instance"),c=b.options;a(c.alsoResize).each(function(){var d=a(this);d.data("ui-resizable-alsoresize",{width:parseInt(d.width(),10),height:parseInt(d.height(),10),left:parseInt(d.css("left"),10),top:parseInt(d.css("top"),10)})})},resize:function(c,e){var b=a(this).resizable("instance"),f=b.options,d=b.originalSize,h=b.originalPosition,g={height:(b.size.height-d.height)||0,width:(b.size.width-d.width)||0,top:(b.position.top-h.top)||0,left:(b.position.left-h.left)||0};a(f.alsoResize).each(function(){var k=a(this),l=a(this).data("ui-resizable-alsoresize"),j={},i=k.parents(e.originalElement[0]).length?["width","height"]:["width","height","top","left"];a.each(i,function(m,o){var n=(l[o]||0)+(g[o]||0);if(n&&n>=0){j[o]=n||null}});k.css(j)})},stop:function(){a(this).removeData("resizable-alsoresize")}});a.ui.plugin.add("resizable","ghost",{start:function(){var c=a(this).resizable("instance"),d=c.options,b=c.size;c.ghost=c.originalElement.clone();c.ghost.css({opacity:0.25,display:"block",position:"relative",height:b.height,width:b.width,margin:0,left:0,top:0}).addClass("ui-resizable-ghost").addClass(typeof d.ghost==="string"?d.ghost:"");c.ghost.appendTo(c.helper)},resize:function(){var b=a(this).resizable("instance");if(b.ghost){b.ghost.css({position:"relative",height:b.size.height,width:b.size.width})}},stop:function(){var b=a(this).resizable("instance");if(b.ghost&&b.helper){b.helper.get(0).removeChild(b.ghost.get(0))}}});a.ui.plugin.add("resizable","grid",{resize:function(){var e,j=a(this).resizable("instance"),n=j.options,h=j.size,i=j.originalSize,k=j.originalPosition,t=j.axis,b=typeof n.grid==="number"?[n.grid,n.grid]:n.grid,r=(b[0]||1),q=(b[1]||1),g=Math.round((h.width-i.width)/r)*r,f=Math.round((h.height-i.height)/q)*q,l=i.width+g,p=i.height+f,d=n.maxWidth&&(n.maxWidth<l),m=n.maxHeight&&(n.maxHeight<p),s=n.minWidth&&(n.minWidth>l),c=n.minHeight&&(n.minHeight>p);n.grid=b;if(s){l+=r}if(c){p+=q}if(d){l-=r}if(m){p-=q}if(/^(se|s|e)$/.test(t)){j.size.width=l;j.size.height=p}else{if(/^(ne)$/.test(t)){j.size.width=l;j.size.height=p;j.position.top=k.top-f}else{if(/^(sw)$/.test(t)){j.size.width=l;j.size.height=p;j.position.left=k.left-g}else{if(p-q<=0||l-r<=0){e=j._getPaddingPlusBorderDimensions(this)}if(p-q>0){j.size.height=p;j.position.top=k.top-f}else{p=q-e.height;j.size.height=p;j.position.top=k.top+i.height-p}if(l-r>0){j.size.width=l;j.position.left=k.left-g}else{l=r-e.width;j.size.width=l;j.position.left=k.left+i.width-l}}}}}});return a.ui.resizable}));