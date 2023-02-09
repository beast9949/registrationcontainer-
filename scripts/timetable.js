!function(e,t){"function"==typeof define&&define.amd?define(["exports"],t):t("undefined"!=typeof exports?exports:e.syncscroll={})}(this,function(e){var t="Width",n="Height",o="Top",r="Left",i="scroll",a="client",s=[],c=function(){var e,c,l,u,d,f=document.getElementsByClassName("syncscroll");for(d in s)if(s.hasOwnProperty(d))for(e=0;e<s[d].length;e++)s[d][e].removeEventListener("scroll",s[d][e].syn,0);for(e=0;e<f.length;e++)if(u=0,l=f[e],d=l.getAttribute("name")){for(l=l.scroller||l,s[d]||(s[d]=[]),c=0;c<s[d].length;c++)s[d][c]==l&&(u=1);u||s[d].push(l),l.eX=l.eY=0,function(e,c){e.addEventListener("scroll",e.syn=function(){var l,u,d=s[c],f=e[i+r],h=e[i+o],p=f/(e[i+t]-e[a+t]),m=h/(e[i+n]-e[a+n]),v=0,E=0;for(f!=e.eX&&(v=1,e.eX=f),h!=e.eY&&(E=1,e.eY=h),u=0;u<d.length;u++)l=d[u],l!=e&&(v&&(f=Math.round(p*(l[i+t]-l[a+t])),l.eX=l[i+r]=f),E&&(h=Math.round(m*(l[i+n]-l[a+n])),l.eY=l[i+o]=h))},0)}(l,d)}};"complete"==document.readyState?c():window.addEventListener("load",c,0),e.reset=c});var Timetable=function(){this.scope={hourStart:0,hourEnd:24},this.usingTwelveHour=!1,this.locations=[],this.events=[]};Timetable.Renderer=function(e){if(!(e instanceof Timetable))throw new Error("Initialize renderer using a Timetable");this.timetable=e},function(){function e(e,n){return t(e)&&t(n)}function t(e){return n(e)&&o(e)}function n(e){return e===parseInt(e,10)}function o(e){return e>=0&&e<25}function r(e,t){return t.indexOf(e)!==-1}function i(e,t){var n=e instanceof Date&&t instanceof Date,o=e<t;return n&&o}function a(e,t){return t>=e?t-e:25+t-e}function s(e){for(;e.firstChild;)e.removeChild(e.firstChild)}function c(e,t){var n;if(t){var o=e>=12?"PM":"AM";n=(e+11)%12+1+":00"+o}else{var r=e<10?"0":"";n=r+e+":00"}return n}Timetable.prototype={setScope:function(t,n){if(!e(t,n))throw new RangeError("Timetable scope should consist of (start, end) in whole hours from 0 to 23");return this.scope.hourStart=t,this.scope.hourEnd=n,this},useTwelveHour:function(){this.usingTwelveHour=!0},addLocations:function(e){function t(){return e instanceof Array}var n=this.locations;if(!t())throw new Error("Tried to add locations in wrong format");return e.forEach(function(e){if(r(e,n))throw new Error("Location already exists");n.push(e)}),this},addEvent:function(e,t,n,o,a){if(!r(t,this.locations))throw new Error("Unknown location");if(!i(n,o))throw new Error("Invalid time range: "+JSON.stringify([n,o]));var s="[object Object]"===Object.prototype.toString.call(a);return this.events.push({name:e,location:t,startDate:n,endDate:o,options:s?a:void 0}),this}},Timetable.Renderer.prototype={draw:function(e){function t(e){if(null===e)throw new Error("Timetable container not found")}function n(e){var t=e.appendChild(document.createElement("aside")),n1=t.appendChild(document.createTextNode("    Channels")),n=t.appendChild(document.createElement("ul"));o(n)}
function o(e){
for(var t=0;t<m.locations.length;t++){
var locationarr=m.locations[t].split("@@");
var n=e.appendChild(document.createElement("li")),
o=n.appendChild(document.createElement("span"));
o.className="row-heading"

 var image = document.createElement("img");
 var imgstr=locationarr[1];
 var textstr=" "+locationarr[0];
 //alert(imgstr);
image.setAttribute("src", locationarr[1]);
  image.setAttribute("width", "40");
  image.setAttribute("height", "40");
o.appendChild(image);
var tt = document.createTextNode(textstr);
o.appendChild(tt);
//o.textContent=m.locations[t]
}
}
function r(e){
var t=e.appendChild(document.createElement("section")),n=i(t),o=t.appendChild(document.createElement("time"));o.className="syncscroll",o.setAttribute("name","scrollheader"),o.setAttribute("id","scrollheader");var r=n.scrollWidth+"px";l(o,r)}function i(e){var t=e.appendChild(document.createElement("header"));t.className="syncscroll",t.setAttribute("name","scrollheader");for(var n=t.appendChild(document.createElement("ul")),o=!1,r=!1,i=m.scope.hourStart;!o;){var a=n.appendChild(document.createElement("li")),s=a.appendChild(document.createElement("span"));s.className="time-label",s.textContent=c(i,m.usingTwelveHour),i!==m.scope.hourEnd||m.scope.hourStart===m.scope.hourEnd&&!r||(o=!0),25===++i&&(i=0,r=!0)}return t}function l(e,t){var n=e.appendChild(document.createElement("ul"));n.style.width=t,n.className="room-timeline";for(var o=0;o<m.locations.length;o++){var r=n.appendChild(document.createElement("li"));u(m.locations[o],r)}}
function u(e,t){
	var arrcolor=[ ' #6497b1 ','#b3cde0'],cnt=0;
	for(var n=0;n<m.events.length;n++){	
	togcolor=arrcolor[cnt];
	var o=m.events[n];o.location===e&&d(o,t,togcolor);
	if(cnt==1){cnt=-1};
	cnt++;
	}
	}
	function d(e,t,togcolor){var n,o,r,i=void 0!==e.options,a=!1;i&&(n=void 0!==e.options.url,o=void 0!==e.options["class"],r=void 0!==e.options.data,a=void 0!==e.options.onClick);var s=n?"a":"span",spanobj=document.createElement(s),c=t.appendChild(spanobj),l=c.appendChild(document.createElement("small"));if(c.title=e.name,n&&(c.href=e.options.url),r)for(var u in e.options.data)c.setAttribute("data-"+u,e.options.data[u]);a&&c.addEventListener("click",function(t){e.options.onClick(e,m,t)}),c.className=o?"time-entry "+e.options["class"]:"time-entry",c.style.background=togcolor,c.style.width=f(e),c.style.left=p(e),l.textContent=e.name}function f(e){var t=e.startDate,n=e.endDate,o=h(t,n);return o/v*100+"%"}function h(e,t){return(t.getTime()-e.getTime())/1e3/60/60}function p(e){var t=m.scope.hourStart,n=e.startDate.getHours()+e.startDate.getMinutes()/60,o=a(t,n);return o/v*100+"%"}var m=this.timetable,v=a(m.scope.hourStart,m.scope.hourEnd),E=document.querySelector(e);t(E),s(E),n(E),r(E),syncscroll.reset()}}}();
	