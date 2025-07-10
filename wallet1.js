// --- AZM2D, KBZPay & WavePay Combined Navigation & Wallet JS ---
// --- Unified API for all wallet actions and UI ---

// ---- NAVIGATION ----
function hideAllSections() {
  const sectionIds = [
    'main-ui', 'profile-ui', 'account-ui', 'help-container',
    'wallet-container', 'kpay-container', 'wave-container'
  ];
  sectionIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
}

function setActiveNav(navId) {
  document.querySelectorAll('.navbar a').forEach(a => a.classList.remove('active'));
  const nav = document.getElementById(navId);
  if (nav) nav.classList.add('active');
}

function safeNavHandler(id, cb) {
  const el = document.getElementById(id);
  if (el) el.onclick = cb;
}

// ---- GLOBAL ADMIN PHONES STATE ----
window.lastWalletAdminPhones = {};

// ---- NAVIGATION EVENTS ----
safeNavHandler('nav-home', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('main-ui');
  if (el) el.style.display = '';
  setActiveNav('nav-home');
});
safeNavHandler('nav-profile', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('profile-ui');
  if (el) el.style.display = '';
  setActiveNav('nav-profile');
});
safeNavHandler('nav-help', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('help-container');
  if (el) el.style.display = '';
  setActiveNav('nav-help');
});
safeNavHandler('nav-wallet', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('wallet-container');
  if (el) el.style.display = '';
  setActiveNav('nav-wallet');
  loadWalletData();
  if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory();
});

// Wallet "Back" button
safeNavHandler('wallet-back', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('main-ui');
  if (el) el.style.display = '';
  setActiveNav('nav-home');
  const hisSec = document.getElementById('deposit-history-section-wallet');
  if (hisSec) hisSec.style.display = 'none';
});

// KBZPay method click from wallet
safeNavHandler('show-kpay-section', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('kpay-container');
  if (el) el.style.display = '';
  setKpayAdminPhone();
});
// KBZPay "Back" button
safeNavHandler('kpay-back-btn', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('wallet-container');
  if (el) el.style.display = '';
  setActiveNav('nav-wallet');
  setKpayAdminPhone();
});
// WavePay method click from wallet
safeNavHandler('show-wave-section', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('wave-container');
  if (el) el.style.display = '';
  showWavePay();
  loadWaveDaiInfo();
  setWaveBalance(lastWalletBalance);
});
// WavePay "Back" button
safeNavHandler('wave-back-btn', function(e) {
  e.preventDefault();
  hideAllSections();
  const el = document.getElementById('wallet-container');
  if (el) el.style.display = '';
  setActiveNav('nav-wallet');
  loadWaveDaiInfo();
});

// ---- WALLET/UI LOGIC ----

let lastWalletBalance = 0; // for Wave UI sync

const balanceElem = document.getElementById('wallet-balance');
const noteElem = document.getElementById('wallet-note');

// Show static wallets (KBZPay & WavePay) -- in your HTML, these are always present!
// This function is now a no-op, left for compatibility
function showStaticWallets() {
  // No dynamic changes required; methods already in HTML.
}

// Show UI for not logged in users
function showNoLogin() {
  lastWalletBalance = 0;
  setAllBalances(0);
  if (noteElem) noteElem.textContent = 'အကောင့်ဝင်ပါ။';
  showStaticWallets();
}

// Show API error message
function showApiWalletError() {
  lastWalletBalance = 0;
  setAllBalances(0);
  if (noteElem) noteElem.textContent = 'API Error!';
  showStaticWallets();
}

// Show dynamic wallet info fetched from API
function showDynamicWallets(data) {
  lastWalletBalance = Number(data.balance);
  if (isNaN(lastWalletBalance)) lastWalletBalance = 0;
  setAllBalances(lastWalletBalance);

  // Store admin phones globally for later use
  window.lastWalletAdminPhones = data.admin_phones || {};

  // Update note (hide if empty)
  const noteSection = document.getElementById('wallet-note-section');
  if (noteElem) {
    if (data.note && data.note.trim() !== "") {
      noteElem.textContent = data.note;
      if (noteSection) noteSection.style.display = '';
    } else {
      noteElem.textContent = '';
      if (noteSection) noteSection.style.display = 'none';
    }
  }

  // Payment method buttons are always in HTML; no dynamic update needed.
  setKpayAdminPhone();
  loadWaveDaiInfo();
}

function setKpayAdminPhone() {
  if (window.lastWalletAdminPhones && window.lastWalletAdminPhones.kpay && document.getElementById('admin-kpay-phone')) {
    document.getElementById('admin-kpay-phone').textContent = window.lastWalletAdminPhones.kpay;
  }
}

// Helper: set all balances (main, kpay, wave, and home)
function setAllBalances(balance) {
  const bal = Number(balance) || 0;
  ['wallet-balance', 'kpay-user-balance', 'wm-balance', 'user-balance'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = Number(bal).toLocaleString();
  });
}
// Helper: for Wave UI, just set balance
function setWaveBalance(balance) {
  const el = document.getElementById('wm-balance');
  if (el) el.textContent = Number(balance || 0).toLocaleString();
}

// Helper: safely get phone from storage
function getPhone() {
  return (
    localStorage.getItem('azm2d_phone') ||
    sessionStorage.getItem('azm2d_phone') ||
    ''
  );
}

// Load wallet data from unified API
function loadWalletData() {
  const phone = getPhone();
  if (!phone) {
    showNoLogin();
    return;
  }
  fetch('https://amazemm.xyz/api/wallet_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'phone=' + encodeURIComponent(phone)
  })
    .then(res => res.json())
    .then(data => {
      if (typeof data === "object" && data !== null && data.success && "wallets" in data) {
        showDynamicWallets(data);
      } else {
        showApiWalletError();
      }
    })
    .catch(showApiWalletError);
}

// ---- INITIAL PAGE LOGIC ----
document.addEventListener('DOMContentLoaded', function() {
  hideAllSections();
  const el = document.getElementById('main-ui');
  if (el) el.style.display = '';
  setActiveNav('nav-home');
  loadWalletData();

  // Kpay Tab Switch Logic
  const tabDeposit = document.getElementById('tab-deposit');
  const tabWithdraw = document.getElementById('tab-withdraw');
  const kpayDepositForm = document.getElementById('kpay-deposit-form');
  const kpayWithdrawForm = document.getElementById('kpay-withdraw-form');

  if (tabDeposit && tabWithdraw && kpayDepositForm && kpayWithdrawForm) {
    tabDeposit.onclick = function() {
      tabDeposit.classList.add('active');
      tabWithdraw.classList.remove('active');
      kpayDepositForm.classList.add('active');
      kpayWithdrawForm.classList.remove('active');
    };
    tabWithdraw.onclick = function() {
      tabWithdraw.classList.add('active');
      tabDeposit.classList.remove('active');
      kpayDepositForm.classList.remove('active');
      kpayWithdrawForm.classList.add('active');
    };
  }

  // Kpay Withdraw Form Submit Handler
  if (kpayWithdrawForm) {
    kpayWithdrawForm.onsubmit = function(e) {
      if (!e) return;
      e.preventDefault();
      const amount = document.getElementById('withdraw-amount').value;
      const phoneField = document.getElementById('withdraw-phone').value;
      const password = document.getElementById('withdraw-password').value;

      // Check phone before submit
      const phone = getPhone();
      if(!phone) {
        showError('withdraw-error', 'အကောင့်ဝင်ပါ။');
        return;
      }
      if (!amount || amount < 1000) {
        showError('withdraw-error', 'ထုတ်ယူမည့်ပမာဏ မှန်ကန်စွာ ထည့်ပါ။');
        return;
      }
      if (!phoneField || !phoneField.match(/^09\d{7,14}$/)) {
        showError('withdraw-error', 'ငွေလက်ခံမည့် KBZPay ဖုန်းနံပါတ် မှန်ကန်စွာ ထည့်ပါ။');
        return;
      }
      if (!password || password.length < 4) {
        showError('withdraw-error', 'Password မှန်ကန်စွာ ထည့်ပါ။');
        return;
      }

      const formData = new FormData();
      formData.append('phone', phone);
      formData.append('action', 'withdraw');
      formData.append('method', 'kpay');
      formData.append('amount', amount);
      formData.append('phone', phoneField);
      formData.append('password', password);

      const succ = document.getElementById('withdraw-success');
      const err = document.getElementById('withdraw-error');
      if (succ) succ.style.display = 'none';
      if (err) err.style.display = 'none';

      fetch('https://amazemm.xyz/api/wallet_api.php', {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            if (succ) {
              succ.style.display = 'block';
              succ.textContent = data.message || 'အောင်မြင်ပါသည်။';
            }
            if (err) err.style.display = 'none';
            const form = document.getElementById('kpay-withdraw-form');
            if (form) form.reset();
            loadWalletData();
            if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory();
          } else {
            if (err) {
              err.style.display = 'block';
              err.textContent = data.error || data.message || 'မှားယွင်းမှုတစ်ခု ဖြစ်ပွားခဲ့သည်။';
            }
          }
        })
        .catch(() => {
          if (err) {
            err.style.display = 'block';
            err.textContent = 'Server သို့ မချိတ်ဆက်နိုင်ပါ။';
          }
        });
    };
  }

});

// ---- WavePay Section Logic ----

// Helper to get token from localStorage/sessionStorage (for Dai info)
function getToken() {
  return localStorage.getItem('azm2d_token') || sessionStorage.getItem('azm2d_token');
}

// Get Wave Dai phone and note from settings API
function loadWaveDaiInfo() {
  // Try to use admin phone from lastWalletAdminPhones first!
  const daiPhone = document.getElementById('wm-dai-phone');
  const note = document.getElementById('wm-note');
  if (window.lastWalletAdminPhones && window.lastWalletAdminPhones.wave && daiPhone) {
    daiPhone.textContent = window.lastWalletAdminPhones.wave;
  }
  // Always fetch for up-to-date info too
  fetch('https://amazemm.xyz/api/wave_dai_info.php')
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (daiPhone) daiPhone.textContent = data.phone || '09XXXXXXXXX';
        if (note) note.textContent = data.note || 'အရေးကြီး: ငွေသွင်း/ထုတ်မှုသည် ဒိုင်လုပ်ဆောင်သည်။ Admin မှ အတည်ပြုမှသာ သင့်အကောင့်တွင် လက်ကျန်ငွေတိုး/လျော့မည်။ Admin မှ ပယ်ချပါက မူရင်း လက်ကျန်ငွေနှင့် ပြန်လည်ထပ်ပေါင်းမည်။';
      }
    });
}

// WavePay Tab switching
safeNavHandler('wm-tab-deposit', function() {
  this.classList.add('active');
  const tabWithdraw = document.getElementById('wm-tab-withdraw');
  if (tabWithdraw) tabWithdraw.classList.remove('active');
  const depositForm = document.getElementById('wm-deposit-form');
  const withdrawForm = document.getElementById('wm-withdraw-form');
  if (depositForm) depositForm.style.display = "block";
  if (withdrawForm) withdrawForm.style.display = "none";
});
safeNavHandler('wm-tab-withdraw', function() {
  this.classList.add('active');
  const tabDeposit = document.getElementById('wm-tab-deposit');
  if (tabDeposit) tabDeposit.classList.remove('active');
  const depositForm = document.getElementById('wm-deposit-form');
  const withdrawForm = document.getElementById('wm-withdraw-form');
  if (depositForm) depositForm.style.display = "none";
  if (withdrawForm) withdrawForm.style.display = "block";
});

// Copy Dai phone number
safeNavHandler('wm-copy-dai-phone', function() {
  const phoneEl = document.getElementById('wm-dai-phone');
  if (phoneEl) {
    const phone = phoneEl.textContent;
    navigator.clipboard.writeText(phone).then(function() {
      alert('ဖုန်းနံပါတ်ကို ကူးယူပြီးပါပြီ။');
    });
  }
});

// WavePay Deposit form submit (Unified API)
const wmDepositForm = document.getElementById('wm-deposit-form');
if (wmDepositForm) {
  wmDepositForm.onsubmit = function(e) {
    if (!e) return;
    e.preventDefault();
    const amount = document.getElementById('wm-deposit-amount').value;
    const txid = document.getElementById('wm-deposit-txid').value;
    const screenshotInput = document.getElementById('wm-deposit-screenshot');
    const screenshot = screenshotInput ? screenshotInput.files[0] : null;

    // Check phone before submit
    const phone = getPhone();
    if(!phone) {
      showError('wm-deposit-error', 'အကောင့်ဝင်ပါ။');
      return;
    }

    if (!amount || amount < 1000) {
      showError('wm-deposit-error', 'ငွေသွင်းပမာဏ မှန်ကန်စွာ ထည့်ပါ။');
      return;
    }
    if (!screenshot && (!txid || txid.length < 6)) {
      showError('wm-deposit-error', 'Screenshot သို့မဟုတ် လုပ်ငန်းစဉ်နောက် ၆လုံး တစ်ခုခု တင်ပါ။');
      return;
    }

    const formData = new FormData();
    formData.append('phone', phone);
    formData.append('action', 'deposit');
    formData.append('method', 'wave');
    formData.append('amount', amount);
    formData.append('txid', txid);
    if (screenshot) formData.append('screenshot', screenshot);

    const succ = document.getElementById('wm-deposit-success');
    const err = document.getElementById('wm-deposit-error');
    if (succ) succ.style.display = 'none';
    if (err) err.style.display = 'none';

    fetch('https://amazemm.xyz/api/wallet_api.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (succ) {
            succ.style.display = 'block';
            succ.textContent = data.message || 'အောင်မြင်ပါသည်။';
          }
          if (err) err.style.display = 'none';
          const form = document.getElementById('wm-deposit-form');
          if (form) form.reset();
          loadWalletData();
          if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory();
        } else {
          if (err) {
            err.style.display = 'block';
            err.textContent = data.error || data.message || 'မှားယွင်းမှုတစ်ခု ဖြစ်ပွားခဲ့သည်။';
          }
        }
      })
      .catch(() => {
        if (err) {
          err.style.display = 'block';
          err.textContent = 'Server သို့ မချိတ်ဆက်နိုင်ပါ။';
        }
      });
  }
}

// WavePay Withdraw form submit (Unified API)
const wmWithdrawForm = document.getElementById('wm-withdraw-form');
if (wmWithdrawForm) {
  wmWithdrawForm.onsubmit = function(e) {
    if (!e) return;
    e.preventDefault();
    const amount = document.getElementById('wm-withdraw-amount').value;
    const phoneField = document.getElementById('wm-withdraw-phone').value;
    const password = document.getElementById('wm-withdraw-password').value;

    // Check phone before submit
    const phone = getPhone();
    if(!phone) {
      showError('wm-withdraw-error', 'အကောင့်ဝင်ပါ။');
      return;
    }

    if (!amount || amount < 1000) {
      showError('wm-withdraw-error', 'ထုတ်ယူမည့်ပမာဏ မှန်ကန်စွာ ထည့်ပါ။');
      return;
    }
    if (!phoneField || !phoneField.match(/^09\d{7,14}$/)) {
      showError('wm-withdraw-error', 'ငွေလက်ခံမည့် WavePay ဖုန်းနံပါတ် မှန်ကန်စွာ ထည့်ပါ။');
      return;
    }
    if (!password || password.length < 4) {
      showError('wm-withdraw-error', 'Password မှန်ကန်စွာ ထည့်ပါ။');
      return;
    }

    const formData = new FormData();
    formData.append('phone', phone);
    formData.append('action', 'withdraw');
    formData.append('method', 'wave');
    formData.append('amount', amount);
    formData.append('phone', phoneField);
    formData.append('password', password);

    const succ = document.getElementById('wm-withdraw-success');
    const err = document.getElementById('wm-withdraw-error');
    if (succ) succ.style.display = 'none';
    if (err) err.style.display = 'none';

    fetch('https://amazemm.xyz/api/wallet_api.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (succ) {
            succ.style.display = 'block';
            succ.textContent = data.message || 'အောင်မြင်ပါသည်။';
          }
          if (err) err.style.display = 'none';
          const form = document.getElementById('wm-withdraw-form');
          if (form) form.reset();
          loadWalletData();
          if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory();
        } else {
          if (err) {
            err.style.display = 'block';
            err.textContent = data.error || data.message || 'မှားယွင်းမှုတစ်ခု ဖြစ်ပွားခဲ့သည်။';
          }
        }
      })
      .catch(() => {
        if (err) {
          err.style.display = 'block';
          err.textContent = 'Server သို့ မချိတ်ဆက်နိုင်ပါ။';
        }
      });
  }
}

// Show error message utility
function showError(elemId, msg) {
  var el = document.getElementById(elemId);
  if (el) {
    el.style.display = 'block';
    el.textContent = msg;
  }
}

// Helper: Hide all main sections
function hideMainSections() {
  if(document.getElementById('main-ui')) document.getElementById('main-ui').style.display = 'none';
  if(document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'none';
  if(document.getElementById('help-container')) document.getElementById('help-container').style.display = 'none';
  if(document.getElementById('profile-ui')) document.getElementById('profile-ui').style.display = 'none';
}

function hideAllNavbars() {
  if(document.getElementById('main-navbar')) document.getElementById('main-navbar').style.display = 'none';
  var kpayNav = document.getElementById('kpay-navbar');
  if (kpayNav) kpayNav.style.display = 'none';
  var waveNav = document.getElementById('wave-navbar');
  if (waveNav) waveNav.style.display = 'none';
}

// Main section navigation
function showSection(section) {
  hideMainSections();
  hideAllNavbars();
  // Hide non-main sections
  if(document.getElementById('wave-container')) document.getElementById('wave-container').style.display = 'none';
  if(document.getElementById('kpay-container')) document.getElementById('kpay-container').style.display = 'none';

  // Show/Hide navbar
  if(['home','wallet','help','profile'].includes(section)) {
    if(document.getElementById('main-navbar')) document.getElementById('main-navbar').style.display = 'flex';
  }

  // Show selected main section
  if(section === 'home' && document.getElementById('main-ui'))      document.getElementById('main-ui').style.display = '';
  if(section === 'wallet' && document.getElementById('wallet-container'))    document.getElementById('wallet-container').style.display = '';
  if(section === 'help' && document.getElementById('help-container'))      document.getElementById('help-container').style.display = '';
  if(section === 'profile' && document.getElementById('profile-ui'))   document.getElementById('profile-ui').style.display = '';

  // Set navbar active
  if(document.getElementById('main-navbar')) {
    document.querySelectorAll('#main-navbar a').forEach(a => a.classList.remove('active'));
    if(section === 'home' && document.getElementById('nav-home'))    document.getElementById('nav-home').classList.add('active');
    if(section === 'wallet' && document.getElementById('nav-wallet'))  document.getElementById('nav-wallet').classList.add('active');
    if(section === 'help' && document.getElementById('nav-help'))    document.getElementById('nav-help').classList.add('active');
    if(section === 'profile' && document.getElementById('nav-profile')) document.getElementById('nav-profile').classList.add('active');
  }
  // Hide deposit history when leaving wallet
  if(section !== 'wallet' && document.getElementById('deposit-history-section-wallet')) document.getElementById('deposit-history-section-wallet').style.display = 'none';
  // Headers
  if(document.getElementById('main-header')) document.getElementById('main-header').style.display = '';
}

// Show WavePay section (show header & wave navbar only)
function showWavePay() {
  hideMainSections();
  hideAllNavbars();
  if(document.getElementById('wave-container')) document.getElementById('wave-container').style.display = '';
  if(document.getElementById('main-header')) document.getElementById('main-header').style.display = 'none';
  if(document.getElementById('wave-navbar')) document.getElementById('wave-navbar').style.display = 'flex';
  loadWaveDaiInfo();
}

// Show Kpay section (show header & kpay navbar only)
function showKpay() {
  hideMainSections();
  hideAllNavbars();
  if(document.getElementById('kpay-container')) document.getElementById('kpay-container').style.display = '';
  if(document.getElementById('main-header')) document.getElementById('main-header').style.display = 'none';
  if(document.getElementById('kpay-navbar')) document.getElementById('kpay-navbar').style.display = 'flex';
  setKpayAdminPhone();
}

// Navbar events (main)
if(document.getElementById('nav-home'))    document.getElementById('nav-home').onclick    = () => { showSection('home'); return false; }
if(document.getElementById('nav-wallet'))  document.getElementById('nav-wallet').onclick  = () => { showSection('wallet'); loadWalletData(); if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory(); return false; }
if(document.getElementById('nav-help'))    document.getElementById('nav-help').onclick    = () => { showSection('help'); return false; }
if(document.getElementById('nav-profile')) document.getElementById('nav-profile').onclick = () => { showSection('profile'); return false; }
// Kpay navbar events
if(document.getElementById('nav-home-kpay'))    document.getElementById('nav-home-kpay').onclick    = () => { showSection('home'); return false; }
if(document.getElementById('nav-wallet-kpay'))  document.getElementById('nav-wallet-kpay').onclick  = () => { showSection('wallet'); loadWalletData(); if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory(); return false; }
if(document.getElementById('nav-help-kpay'))    document.getElementById('nav-help-kpay').onclick    = () => { showSection('help'); return false; }
if(document.getElementById('nav-profile-kpay')) document.getElementById('nav-profile-kpay').onclick = () => { showSection('profile'); return false; }
// WavePay navbar events
if(document.getElementById('nav-home-wave'))    document.getElementById('nav-home-wave').onclick    = () => { showSection('home'); return false; }
if(document.getElementById('nav-wallet-wave'))  document.getElementById('nav-wallet-wave').onclick  = () => { showSection('wallet'); loadWalletData(); if (typeof loadWalletTransactionHistory === "function") loadWalletTransactionHistory(); return false; }
if(document.getElementById('nav-help-wave'))    document.getElementById('nav-help-wave').onclick    = () => { showSection('help'); return false; }
if(document.getElementById('nav-profile-wave')) document.getElementById('nav-profile-wave').onclick = () => { showSection('profile'); return false; }

// Wallet method buttons (to show WavePay / Kpay)
if(document.getElementById('show-wave-section')) document.getElementById('show-wave-section').onclick = function(e) {
  e.preventDefault();
  showWavePay();
};
if(document.getElementById('show-kpay-section')) document.getElementById('show-kpay-section').onclick = function(e) {
  e.preventDefault();
  showKpay();
};

// WavePay back button
if(document.getElementById('wave-back-btn')) document.getElementById('wave-back-btn').onclick = function(e) {
  e.preventDefault();
  showSection('wallet');
  loadWaveDaiInfo();
};

// Kpay back button
if(document.getElementById('kpay-back-btn')) document.getElementById('kpay-back-btn').onclick = function(e) {
  e.preventDefault();
  showSection('wallet');
  setKpayAdminPhone();
};

// Show deposit history inside wallet when clicking "History" (already handled above)
if(document.getElementById('wallet-back')) document.getElementById('wallet-back').onclick = function() {
  showSection('home');
  if(document.getElementById('deposit-history-section-wallet')) document.getElementById('deposit-history-section-wallet').style.display = 'none';
  return false;
}

// On page load, show home
showSection('home');