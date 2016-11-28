/*
 Highcharts JS v5.0.4 (2016-11-25)
 Gantt series

 (c) 2016 Lars A. V. Cabrera

 --- WORK IN PROGRESS ---

 License: www.highcharts.com/license
*/
(function(p){"object"===typeof module&&module.exports?module.exports=p:p(Highcharts)})(function(p){(function(h){var p=h.dateFormat,v=h.each,w=h.isObject,r=h.pick,m=h.wrap,q=h.Axis,x=h.Chart,t=h.Tick;q.prototype.isOuterAxis=function(){var a=this,c=-1,b=!0;v(this.chart.axes,function(d,g){d.side===a.side&&(d===a?c=g:0<=c&&g>c&&(b=!1))});return b};t.prototype.getLabelWidth=function(){return this.label.getBBox().width};q.prototype.getMaxLabelLength=function(a){var c=this.tickPositions,b=this.ticks,d=0;
if(!this.maxLabelLength||a)v(c,function(a){(a=b[a])&&a.labelLength>d&&(d=a.labelLength)}),this.maxLabelLength=d;return this.maxLabelLength};q.prototype.addTitle=function(){var a=this.chart.renderer,c=this.axisParent,b=this.horiz,d=this.opposite,g=this.options,e=g.title,f;this.showAxis=f=this.hasData()||r(g.showEmpty,!0);g.title="";this.axisTitle||((g=e.textAlign)||(g=(b?{low:"left",middle:"center",high:"right"}:{low:d?"right":"left",middle:"center",high:d?"left":"right"})[e.align]),this.axisTitle=
a.text(e.text,0,0,e.useHTML).attr({zIndex:7,rotation:e.rotation||0,align:g}).addClass("highcharts-axis-title").css(e.style).add(c),this.axisTitle.isNew=!0);this.axisTitle[f?"show":"hide"](!0)};h.dateFormats={W:function(a){a=new Date(a);var c=0===a.getUTCDay()?7:a.getUTCDay(),b=a.getTime(),d=new Date(a.getUTCFullYear(),0,1,-6);a.setDate(a.getUTCDate()+4-c);return 1+Math.floor(Math.floor((b-d)/864E5)/7)},E:function(a){return p("%a",a,!0).charAt(0)}};m(t.prototype,"addLabel",function(a){var c=this.axis,
b=void 0!==c.options.categories,d=c.tickPositions,d=this.pos!==d[d.length-1];(!c.options.grid||b||d)&&a.apply(this)});m(t.prototype,"getLabelPosition",function(a,c,b,d){var g=a.apply(this,Array.prototype.slice.call(arguments,1)),e=this.axis,f=e.options,k=f.tickInterval||1,n,u;f.grid&&(n=f.labels.style.fontSize,u=e.chart.renderer.fontMetrics(n,d),n=u.b,u=u.h,e.horiz&&void 0===f.categories?(f=e.axisGroup.getBBox().height,k=this.pos+k/2,g.x=e.translate(k)+e.left,k=f/2+u/2-Math.abs(u-n),g.y=0===e.side?
b-k:b+k):(void 0===f.categories&&(k=this.pos+k/2,g.y=e.translate(k)+e.top+n/2),k=this.getLabelWidth()/2-e.maxLabelLength/2,g.x=3===e.side?g.x+k:g.x-k));return g});m(q.prototype,"tickSize",function(a){var c=a.apply(this,Array.prototype.slice.call(arguments,1)),b;this.options.grid&&!this.horiz&&(b=2*Math.abs(this.defaultLeftAxisOptions.labels.x),this.maxLabelLength||(this.maxLabelLength=this.getMaxLabelLength()),b=this.maxLabelLength+b,c[0]=b);return c});m(q.prototype,"getOffset",function(a){var c=
this.chart.axisOffset,b=this.side,d,g,e=this.options,f=e.title,k=f&&f.text&&!1!==f.enabled;this.options.grid&&w(this.options.title)?(g=this.tickSize("tick")[0],c[b]&&g&&(d=c[b]+g),k&&this.addTitle(),a.apply(this,Array.prototype.slice.call(arguments,1)),c[b]=r(d,c[b]),e.title=f):a.apply(this,Array.prototype.slice.call(arguments,1))});m(q.prototype,"renderUnsquish",function(a){this.options.grid&&(this.labelRotation=0,this.options.labels.rotation=0);a.apply(this)});m(q.prototype,"setOptions",function(a,
c){c.grid&&this.horiz&&(c.startOnTick=!0,c.minPadding=0,c.endOnTick=!0);a.apply(this,Array.prototype.slice.call(arguments,1))});m(q.prototype,"render",function(a){var c=this.options,b,d,g,e,f,k,n=this.chart.renderer;if(c.grid){if(b=2*Math.abs(this.defaultLeftAxisOptions.labels.x),b=this.maxLabelLength+b,d=c.lineWidth,this.rightWall&&this.rightWall.destroy(),a.apply(this),a=this.axisGroup.getBBox(),this.horiz&&(this.rightWall=n.path(["M",a.x+this.width+1,a.y,"L",a.x+this.width+1,a.y+a.height]).attr({stroke:c.tickColor||
"#ccd6eb","stroke-width":c.tickWidth||1,zIndex:7,class:"grid-wall"}).add(this.axisGroup)),this.isOuterAxis()&&this.axisLine&&(this.horiz&&(b=a.height-1),d)){a=this.getLinePath(d);f=a.indexOf("M")+1;k=a.indexOf("L")+1;g=a.indexOf("M")+2;e=a.indexOf("L")+2;if(0===this.side||3===this.side)b=-b;this.horiz?(a[g]+=b,a[e]+=b):(a[f]+=b,a[k]+=b);this.axisLineExtra?this.axisLineExtra.animate({d:a}):this.axisLineExtra=n.path(a).attr({stroke:c.lineColor,"stroke-width":d,zIndex:7}).add(this.axisGroup);this.axisLine[this.showAxis?
"show":"hide"](!0)}}else a.apply(this)});m(x.prototype,"render",function(a){var c=25/11,b,d;v(this.axes,function(a){var e=a.options;e.grid&&(d=e.labels.style.fontSize,b=a.chart.renderer.fontMetrics(d),"datetime"===e.type&&(e.units=[["millisecond",[1]],["second",[1]],["minute",[1]],["hour",[1]],["day",[1]],["week",[1]],["month",[1]],["year",null]]),a.horiz?e.tickLength=e.cellHeight||b.h*c:(e.tickWidth=1,e.lineWidth||(e.lineWidth=1)))});a.apply(this)})})(p);(function(h){var p=h.getOptions().plotOptions,
v=h.Color,w=h.seriesTypes.column,r=h.each,m=h.extendClass,q=h.isNumber,x=h.isObject,t=h.merge,a=h.pick,c=h.seriesTypes,b=h.wrap,d=h.Axis,g=h.Point,e=h.Series;p.xrange=t(p.column,{tooltip:{pointFormat:'\x3cspan style\x3d"color:{point.color}"\x3e\u25cf\x3c/span\x3e {series.name}: \x3cb\x3e{point.yCategory}\x3c/b\x3e\x3cbr/\x3e'}});c.xrange=m(w,{pointClass:m(g,{getLabelConfig:function(){var a=g.prototype.getLabelConfig.call(this);a.x2=this.x2;a.yCategory=this.yCategory=this.series.yAxis.categories&&
this.series.yAxis.categories[this.y];return a}}),type:"xrange",forceDL:!0,parallelArrays:["x","x2","y"],requireSorting:!1,animate:c.line.prototype.animate,getColumnMetrics:function(){function a(){r(c.series,function(a){var b=a.xAxis;a.xAxis=a.yAxis;a.yAxis=b})}var b,c=this.chart;a();this.yAxis.closestPointRange=1;b=w.prototype.getColumnMetrics.call(this);a();return b},cropData:function(a,b,c,d){b=e.prototype.cropData.call(this,this.x2Data,b,c,d);b.xData=a.slice(b.start,b.end);return b},translate:function(){w.prototype.translate.apply(this,
arguments);var b=this.xAxis,c=this.columnMetrics,e=this.options.minPointLength||0;r(this.points,function(d){var f=d.plotX,l=a(d.x2,d.x+(d.len||0)),l=b.toPixels(l,!0),g=l-f,h;e&&(h=e-g,0>h&&(h=0),f-=h/2,l+=h/2);f=Math.max(f,-10);l=Math.min(Math.max(l,-10),b.len+10);d.shapeArgs={x:f,y:d.plotY+c.offset,width:l-f,height:c.width};d.tooltipPos[0]+=g/2;d.tooltipPos[1]-=c.width/2;if(l=d.partialFill)x(l)&&(l=l.amount),q(l)||(l=0),f=d.shapeArgs,d.partShapeArgs={x:f.x,y:f.y+1,width:f.width*l,height:f.height-
2}})},drawPoints:function(){var a=this,b=this.chart,d=a.options,c=b.renderer,e=b.pointCount<(d.animationLimit||250)?"animate":"attr";r(a.points,function(b){var f=b.graphic,g=b.shapeType,h=b.shapeArgs,k=b.partShapeArgs,l=a.options,n=b.partialFill,m=b.selected&&"select",p=d.stacking&&!d.borderRadius;if(q(b.plotY)&&null!==b.y){if(f){if(b.graphicOriginal[e](t(h)),k)b.graphicOverlay[e](t(k))}else b.graphic=f=c.g("point").attr({"class":b.getClassName()}).add(b.group||a.group),b.graphicOriginal=c[g](h).addClass("highcharts-partfill-original").add(f),
k&&(b.graphicOverlay=c[g](k).addClass("highcharts-partfill-overlay").add(f));b.graphicOriginal.attr(a.pointAttribs(b,m)).shadow(d.shadow,null,p);k&&(x(n)||(n={}),x(l.partialFill)&&(n=t(n,l.partialFill)),f=n.fill||v(a.color).brighten(-.3).get("rgb"),b.graphicOverlay.attr(a.pointAttribs(b,m)).attr("fill",f).attr("stroke-width",0).shadow(d.shadow,null,p))}else f&&(b.graphic=f.destroy())})}});b(d.prototype,"getSeriesExtremes",function(b){var d=this.series,c,e;b.call(this);this.isXAxis&&"xrange"===d.type&&
(c=a(this.dataMax,Number.MIN_VALUE),r(this.series,function(a){r(a.x2Data||[],function(a){a>c&&(c=a,e=!0)})}),e&&(this.dataMax=c))})})(p)});
