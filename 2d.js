function fetch2dData() {
  const token = localStorage.getItem('azm2d_token');
  if (!token) {
    window.location.href = '/index.html';
    return;
  }
  fetch('/b/2dnumber.php?api=1', { // ✅ Fixed: use correct API endpoint with ?api=1
    headers: { 'Authorization': 'Bearer ' + token }
  })
    .then(r => {
      if (!r.ok) {
        // Unauthorized or server error
        localStorage.clear();
        window.location.href = '/index.html';
        return Promise.reject('Unauthorized or error');
      }
      return r.json();
    })
    .then(data => {
      if (!data || data.error === 'Unauthorized') {
        localStorage.clear();
        window.location.href = '/index.html';
        return;
      }
      // DOM update logic (example)
      document.getElementById('Balance').textContent = data.Balance ?? 0;
      // Update your UI for other fields as needed
    })
    .catch(() => {
      alert('2D Data fetch error!');
      window.location.href = '/index.html';
    });
}