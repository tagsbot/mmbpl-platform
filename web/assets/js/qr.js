// Renders QR codes into elements with [data-qr="text"] using qrcode-generator (MIT).
(function(){
  function load(cb){ if (window.qrcode) return cb(); var s=document.createElement('script'); s.src=(window.MMBPL?MMBPL.base:'')+'/assets/js/qrcode.js'; s.onload=cb; document.head.appendChild(s); }
  load(function(){
    document.querySelectorAll('[data-qr]').forEach(function(el){
      var q=qrcode(0,'M'); q.addData(el.getAttribute('data-qr')); q.make();
      var size=parseInt(el.getAttribute('data-size')||'180',10), cells=q.getModuleCount(), cell=Math.max(2,Math.floor(size/cells));
      el.innerHTML=q.createSvgTag({cellSize:cell,margin:0,scalable:true});
      var svg=el.querySelector('svg'); if(svg){svg.setAttribute('width',size);svg.setAttribute('height',size);}
    });
  });
})();
