const v = document.getElementById("background-video");

function showPlayOverlay() {
  const btn = document.createElement('button');
  btn.className = 'video-play';
  btn.textContent = 'Play';
  Object.assign(btn.style, {
    position:'absolute', inset:'0', margin:'auto', width:'120px', height:'48px',
    borderRadius:'999px', border:'1px solid #fff8', color:'#fff', background:'rgba(0,0,0,.35)',
    cursor:'pointer', zIndex:'10'
  });
  v.parentElement.style.position = 'relative';
  v.after(btn);

  const resume = async () => {
    try {
      // optional: unmute after a user gesture
      // v.muted = false;
      await v.play();
      btn.remove();
    } catch(e) { console.warn('Could not start video:', e); }
  };
  btn.addEventListener('click', resume, { once:true });
  // Also allow keyboard as gesture
  document.addEventListener('keydown', resume, { once:true });
}

async function attemptAutoplay() {
  try {
    v.muted = true;                 // ensure muted
    await v.play();                 // browsers return a Promise
    // success: do nothing
  } catch (err) {
    console.warn('Autoplay blocked:', err);
    showPlayOverlay();              // show manual play
  }
}

// Only try when video is visible (helps with mobile & performance)
if ('IntersectionObserver' in window) {
  const io = new IntersectionObserver(([e]) => {
    if (e.isIntersecting) {
      attemptAutoplay();
      io.disconnect();
    }
  }, { threshold: .25 });
  io.observe(v);
} else {
  document.addEventListener('DOMContentLoaded', attemptAutoplay);
}