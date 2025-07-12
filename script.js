// Debug mode toggle
let debugMode = false;

// Current view state
let currentView = 'home';

// Helper functions for API and storage
const AUTH = {
  getToken: function() {
    return localStorage.getItem('azm2d_token') || sessionStorage.getItem('azm2d_token');
  },

  getPhone: function() {
    return localStorage.getItem('azm2d_phone') || sessionStorage.getItem('azm2d_phone');
  },

  setLogin: function(phone, token, remember = false) {
    if (remember) {
      localStorage.setItem('azm2d_phone', phone);
      localStorage.setItem('azm2d_token', token);
    } else {
      sessionStorage.setItem('azm2d_phone', phone);
      sessionStorage.setItem('azm2d_token', token);
    }
  },

  clearLogin: function() {
    localStorage.removeItem('azm2d_phone');
    localStorage.removeItem('azm2d_token');
    sessionStorage.removeItem('azm2d_phone');
    sessionStorage.removeItem('azm2d_token');
  },

  isLoggedIn: function() {
    return !!(this.getToken());
  }
};

// Debug logging
function debugLog(message, data) {
  if (!debugMode) return;
  console.log(`[DEBUG] ${message}:`, data);
  const debugContent = document.getElementById('debug-content');
  if (debugContent) {
    const timestamp = new Date().toLocaleTimeString();
    let dataText = '';
    try {
      dataText = typeof data === 'object' ? JSON.stringify(data, null, 2) : String(data);
    } catch (e) {
      dataText = '[Error displaying data]';
    }
    // Do not show "Debug Mode Enabled" in UI, skip adding if message contains it
    if (!/Debug Mode Enabled/i.test(message)) {
      debugContent.innerHTML += `<div><strong>${timestamp}</strong>: ${message}<br><span>${dataText}</span></div><hr>`;
      debugContent.scrollTop = debugContent.scrollHeight;
    }
  }
}

// UI helper functions
function showLoading() {
  const spinner = document.getElementById('loading-spinner');
  if (spinner) spinner.style.display = 'block';
}

function hideLoading() {
  const spinner = document.getElementById('loading-spinner');
  if (spinner) spinner.style.display = 'none';
}

function showApiError(show = true) {
  const errorMsg = document.getElementById('api-error-message');
  if (errorMsg) errorMsg.style.display = show ? 'block' : 'none';
}

function formatNumber(x) {
  if (!x && x !== 0) return "0";
  return Number(x).toLocaleString();
}

// View management
function showView(view) {
  currentView = view;
  document.querySelectorAll('.navbar a').forEach(el => el.classList.remove('active'));
  const activeNav = document.getElementById(`nav-${view}`);
  if (activeNav) activeNav.classList.add('active');
  document.getElementById('main-ui').style.display = 'none';
  document.getElementById('profile-ui').style.display = 'none';
  document.getElementById('account-ui').style.display = 'none';
  if (document.getElementById('help-container')) document.getElementById('help-container').style.display = 'none';
  if (document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'none';

  if (view === 'home') {
    document.getElementById('main-ui').style.display = 'block';
    loadHomePage();
  } else if (view === 'wallet') {
    if (document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'block';
    loadWalletData();
  } else if (view === 'profile') {
    document.getElementById('profile-ui').style.display = 'block';
    loadProfilePage();
  } else if (view === 'login' || view === 'register') {
    document.getElementById('account-ui').style.display = 'block';
    showTab(view === 'register' ? 'register' : 'login');
  } else if (view === 'help') {
    if (document.getElementById('help-container')) document.getElementById('help-container').style.display = 'block';
  }
  window.scrollTo(0, 0);
}

// Account/Login/Register logic
function showTab(tab) {
  document.getElementById('register-form').style.display = tab === 'register' ? 'block' : 'none';
  document.getElementById('login-form').style.display = tab === 'login' ? 'block' : 'none';
  document.getElementById('tab-register').classList.toggle('active', tab === 'register');
  document.getElementById('tab-login').classList.toggle('active', tab === 'login');
  document.querySelectorAll('.success-msg,.error-msg').forEach(e => e.style.display = 'none');
  document.querySelectorAll('.form-input').forEach(e => e.classList.remove('error'));
}

function loadAdBanner() {
  fetch('https://amazemm.xyz/api/ad_banner_api.php')
    .then(response => {
      if (!response.ok) throw new Error('Network response was not ok');
      return response.json();
    })
    .then(data => {
      if(data.success && data.banner) {
        document.getElementById('ad-banner-img').src = data.banner.image_url;
        document.getElementById('ad-banner-title').style.display = "none";
        document.getElementById('ad-banner-desc').style.display = "none";
        // Marquee message inside ad banner
        if (data.marquee_message) {
          document.getElementById('ad-banner-marquee').innerHTML =
            `<marquee scrollamount="5" behavior="scroll" direction="left">${data.marquee_message}</marquee>`;
        } else {
          document.getElementById('ad-banner-marquee').innerHTML = '';
        }
      }
    })
    .catch(error => {
      debugLog('Ad banner error:', error);
      document.querySelector('.ad-banner').style.display = 'none';
    });
}

// Load home page data
function loadHomePage() {
  debugLog('Loading home page', {});
  loadAdBanner();
  loadUserBalance();
}

// Load profile page 
function loadProfilePage() {
  debugLog('Loading profile page', {});
  fetchUserProfile();
}

// Load user balance
function loadUserBalance() {
  const token = AUTH.getToken();
  const phone = AUTH.getPhone();
  if (!token || !phone) return;
  debugLog('Loading user balance', {});
  fetch('https://amazemm.xyz/api/user.php', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Authorization': token 
    },
    body: JSON.stringify({ phone: phone })
  })
  .then(response => {
    if (!response.ok) throw new Error('Network response was not ok');
    return response.json();
  })
  .then(data => {
    debugLog('User data received', data);
    if (data.success && data.user) {
      document.getElementById('user-balance').textContent = formatNumber(data.user.balance || 0);
    } else {
      debugLog('User data invalid, logging out', data);
      AUTH.clearLogin();
      showView('login');
    }
  })
  .catch(error => {
    debugLog('Error fetching user data', error.message);
    // API fail, but keep UI as is
  });
}

// API request helper for profile
async function callProfileApi(actionName, additionalData = {}) {
  const phone = AUTH.getPhone();
  const token = AUTH.getToken();
  if (!phone || !token) {
    debugLog(`No auth data for ${actionName}`, { phone });
    return null;
  }
  const requestData = { action: actionName, ...additionalData };
  debugLog(`API Request: ${actionName}`, requestData);
  try {
    const response = await fetch('https://amazemm.xyz/api/profile.php', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Authorization': token 
      },
      body: JSON.stringify(requestData)
    });
    debugLog(`API Response Status: ${actionName}`, response.status);
    const responseText = await response.text();
    if (!responseText) throw new Error('Empty response from server');
    let data;
    try {
      data = JSON.parse(responseText);
      debugLog(`API Response Data: ${actionName}`, data);
      return data;
    } catch (e) {
      debugLog('JSON Parse Error', { responseText, error: e.message });
      throw new Error('Invalid response format from server');
    }
  } catch (error) {
    debugLog(`API Error: ${actionName}`, error.message);
    throw error;
  }
}

// Fetch user profile
async function fetchUserProfile() {
  showLoading();
  showApiError(false);
  try {
    if (!AUTH.isLoggedIn()) {
      debugLog('Not logged in', {});
      showView('login');
      return;
    }
    debugLog('Fetching user profile', { phone: AUTH.getPhone() });
    const data = await callProfileApi('get_profile');
    if (data && data.success && data.user) {
      updateProfileUI(data.user);
    } else {
      showApiError(true);
      debugLog('Invalid API response', data);
      setTimeout(() => {
        AUTH.clearLogin();
        showView('login');
      }, 2000);
    }
  } catch (error) {
    debugLog('API Error', error.message);
    showApiError(true);
  } finally {
    hideLoading();
  }
}

// Update profile UI
function updateProfileUI(user) {
  debugLog('Updating UI with user data', user);
  document.getElementById('profile-name').textContent = user.name || 'N/A';
  document.getElementById('profile-phone').textContent = user.phone || 'N/A';
  const avatarElement = document.getElementById('profile-avatar');
  if (user.name && user.name.length > 0) {
    avatarElement.innerHTML = user.name.charAt(0).toUpperCase();
  } else {
    avatarElement.innerHTML = '<i class="fas fa-user"></i>';
  }
  document.getElementById('balance-amount').textContent = formatNumber(user.balance || 0);
  document.getElementById('info-name').textContent = user.name || 'N/A';
  document.getElementById('info-phone').textContent = user.phone || 'N/A';
  document.getElementById('info-agent-code').textContent = user.agent_code || 'N/A';
}

// Change password
async function changePassword(currentPassword, newPassword) {
  if (!AUTH.isLoggedIn()) {
    debugLog('No auth data for password change', {});
    showView('login');
    return false;
  }
  const submitBtn = document.getElementById('password-submit-btn');
  submitBtn.classList.add('loading');
  submitBtn.disabled = true;
  try {
    debugLog('Changing password', {
      currentPasswordLength: currentPassword.length,
      newPasswordLength: newPassword.length
    });
    const data = await callProfileApi('change_password', {
      current_password: currentPassword,
      new_password: newPassword
    });
    if (data && data.success) {
      document.getElementById('password-success-message').style.display = 'block';
      document.getElementById('password-error-message').style.display = 'none';
      document.getElementById('change-password-form').reset();
      setTimeout(() => {
        document.getElementById('password-success-message').style.display = 'none';
      }, 5000);
      return true;
    } else {
      document.getElementById('password-error-message').textContent = data?.message || 'စကားဝှက် ပြောင်းရန် မအောင်မြင်ပါ။';
      document.getElementById('password-error-message').style.display = 'block';
      document.getElementById('password-success-message').style.display = 'none';
      return false;
    }
  } catch (error) {
    debugLog('Password Change Error', error.message);
    document.getElementById('password-error-message').textContent = 'Server နှင့် ဆက်သွယ်၍မရပါ။';
    document.getElementById('password-error-message').style.display = 'block';
    document.getElementById('password-success-message').style.display = 'none';
    return false;
  } finally {
    submitBtn.classList.remove('loading');
    submitBtn.disabled = false;
  }
}

// Check password strength
function checkPasswordStrength(password) {
  const requirements = [
    { regex: /.{6,}/, element: document.getElementById("length-req") },
    { regex: /[0-9]/, element: document.getElementById("number-req") },
    { regex: /[A-Z]/, element: document.getElementById("uppercase-req") }
  ];
  let strength = 0;
  requirements.forEach(req => {
    if (req.element) {
      if (req.regex.test(password)) {
        strength++;
        req.element.classList.add('requirement-met');
        req.element.classList.remove('requirement-unmet');
        req.element.querySelector('i').className = 'fas fa-check';
      } else {
        req.element.classList.remove('requirement-met');
        req.element.classList.add('requirement-unmet');
        req.element.querySelector('i').className = 'fas fa-circle';
      }
    }
  });
  return strength;
}

// Validate form fields
function validateField(field, pattern, errorMessage) {
  const value = field.value.trim();
  const isValid = pattern.test(value);
  const errorElement = document.getElementById(`${field.id}-error`);
  if (!isValid) {
    field.classList.add('error');
    if (errorElement) {
      errorElement.textContent = errorMessage;
      errorElement.style.display = 'block';
    }
  } else {
    field.classList.remove('error');
    if (errorElement) {
      errorElement.style.display = 'none';
    }
  }
  return isValid;
}

// Handle login
function handleLogin(e) {
  e.preventDefault();
  var phone = document.getElementById('login-phone').value.trim();
  var password = document.getElementById('login-password').value;
  var rememberMe = document.getElementById('remember-me').checked;
  var submitBtn = document.getElementById('login-submit-btn');
  var errorMsg = document.getElementById('login-error-msg');
  var successMsg = document.getElementById('login-success-msg');
  errorMsg.style.display = 'none';
  successMsg.style.display = 'none';
  let isValid = true;
  if (!phone.match(/^[0-9]{7,15}$/)) {
    document.getElementById('login-phone').classList.add('error');
    document.getElementById('login-phone-error').textContent = 'ဖုန်းနံပါတ် မှန်ကန်စွာ ထည့်ပါ။';
    document.getElementById('login-phone-error').style.display = 'block';
    isValid = false;
  } else {
    document.getElementById('login-phone').classList.remove('error');
    document.getElementById('login-phone-error').style.display = 'none';
  }
  if (!password || password.length < 6) {
    document.getElementById('login-password').classList.add('error');
    document.getElementById('login-password-error').textContent = 'စကားဝှက် မှန်ကန်စွာ ထည့်ပါ။';
    document.getElementById('login-password-error').style.display = 'block';
    isValid = false;
  } else {
    document.getElementById('login-password').classList.remove('error');
    document.getElementById('login-password-error').style.display = 'none';
  }
  if (!isValid) return;
  submitBtn.classList.add('loading');
  submitBtn.disabled = true;
  debugLog('Login request', { phone, passwordLength: password.length, rememberMe });
  fetch('https://amazemm.xyz/api/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      phone: phone,
      password: password
    })
  })
  .then(response => {
    debugLog('Login Response Status', response.status);
    return response.text().then(text => {
      if (!text) throw new Error('Server returned empty response');
      try {
        return JSON.parse(text);
      } catch (e) {
        debugLog('JSON Parse Error', { text, error: e.message });
        throw new Error('Invalid response format from server');
      }
    });
  })
  .then(data => {
    debugLog('Login Response Data', data);
    if(data.success) {
      successMsg.textContent = 'အကောင့်ဝင်ခြင်း အောင်မြင်ပါသည်။';
      successMsg.style.display = 'block';
      if(data.token) {
        AUTH.setLogin(phone, data.token, rememberMe);
        setTimeout(() => { showView('home'); }, 1000);
      }
    } else {
      errorMsg.textContent = data.message || 'မှားယွင်းမှုတစ်ခု ဖြစ်ပွားခဲ့သည်။';
      errorMsg.style.display = 'block';
    }
  })
  .catch(error => {
    debugLog('Login Error', error.message);
    errorMsg.textContent = 'Server သို့ ချိတ်ဆက်၍မရပါ။ နောက်မှ ပြန်လည်ကြိုးစားပါ။';
    errorMsg.style.display = 'block';
  })
  .finally(() => {
    submitBtn.classList.remove('loading');
    submitBtn.disabled = false;
  });
}

// Handle registration
function handleRegister(e) {
  e.preventDefault();
  var name = document.getElementById('reg-name').value.trim();
  var phone = document.getElementById('reg-phone').value.trim();
  var password = document.getElementById('reg-password').value;
  var agentCode = document.getElementById('reg-agent_code').value.trim();
  var submitBtn = document.getElementById('reg-submit-btn');
  var errorMsg = document.getElementById('reg-error-msg');
  var successMsg = document.getElementById('reg-success-msg');
  errorMsg.style.display = 'none';
  successMsg.style.display = 'none';
  let isValid = true;
  if (name.length < 2) {
    document.getElementById('reg-name').classList.add('error');
    document.getElementById('reg-name-error').textContent = 'အမည် မှန်ကန်စွာ ထည့်ပါ။';
    document.getElementById('reg-name-error').style.display = 'block';
    isValid = false;
  } else {
    document.getElementById('reg-name').classList.remove('error');
    document.getElementById('reg-name-error').style.display = 'none';
  }
  if (!phone.match(/^[0-9]{7,15}$/)) {
    document.getElementById('reg-phone').classList.add('error');
    document.getElementById('reg-phone-error').textContent = 'ဖုန်းနံပါတ် မှန်ကန်စွာ ထည့်ပါ။';
    document.getElementById('reg-phone-error').style.display = 'block';
    isValid = false;
  } else {
    document.getElementById('reg-phone').classList.remove('error');
    document.getElementById('reg-phone-error').style.display = 'none';
  }
  if (password.length < 6) {
    document.getElementById('reg-password').classList.add('error');
    document.getElementById('reg-password-error').textContent = 'စကားဝှက် အနည်းဆုံး ၆ လုံးရှိရပါမည်။';
    document.getElementById('reg-password-error').style.display = 'block';
    isValid = false;
  } else {
    document.getElementById('reg-password').classList.remove('error');
    document.getElementById('reg-password-error').style.display = 'none';
  }
  if (!isValid) return;
  submitBtn.classList.add('loading');
  submitBtn.disabled = true;
  debugLog('Registration request', { name, phone, passwordLength: password.length, agentCode });
  const registrationData = {
    name: name,
    phone: phone,
    password: password,
    agent_code: agentCode
  };
  fetch('https://amazemm.xyz/api/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(registrationData)
  })
  .then(response => {
    debugLog('Registration Response Status', response.status);
    return response.text().then(text => {
      if (!text) throw new Error('Server returned empty response');
      try {
        return JSON.parse(text);
      } catch (e) {
        debugLog('JSON Parse Error', { text, error: e.message });
        throw new Error('Invalid response format from server');
      }
    });
  })
  .then(data => {
    debugLog('Registration Response Data', data);
    if(data.success) {
      successMsg.textContent = 'အကောင့်ဖွင့်ခြင်း အောင်မြင်ပါသည်။';
      successMsg.style.display = 'block';
      document.getElementById('register-form').reset();
      if(data.token) {
        AUTH.setLogin(phone, data.token, false);
        setTimeout(() => { showView('home'); }, 1500);
      }
    } else {
      errorMsg.textContent = data.message || 'မှားယွင်းမှုတစ်ခု ဖြစ်ပွားခဲ့သည်။';
      errorMsg.style.display = 'block';
    }
  })
  .catch(error => {
    debugLog('Registration Error', error.message);
    errorMsg.textContent = 'Server သို့ ချိတ်ဆက်၍မရပါ။ နောက်မှ ပြန်လည်ကြိုးစားပါ။';
    errorMsg.style.display = 'block';
  })
  .finally(() => {
    submitBtn.classList.remove('loading');
    submitBtn.disabled = false;
  });
}

// Handle password input for strength checker
function handlePasswordInput() {
  const password = document.getElementById('reg-password').value;
  checkPasswordStrength(password);
}

// Handle change password form submission
async function handleChangePassword(e) {
  e.preventDefault();
  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  if (newPassword !== confirmPassword) {
    document.getElementById('password-error-message').textContent = 'စကားဝှက် အသစ်နှင့် အတည်ပြုစကားဝှက်တို့ မတူညီပါ။';
    document.getElementById('password-error-message').style.display = 'block';
    return;
  }
  if (newPassword.length < 6) {
    document.getElementById('password-error-message').textContent = 'စကားဝှက်သည် အနည်းဆုံး ၆ လုံး ရှိရပါမည်။';
    document.getElementById('password-error-message').style.display = 'block';
    return;
  }
  await changePassword(currentPassword, newPassword);
}

// Handle logout
function handleLogout() {
  debugLog('Logout button clicked', {});
  AUTH.clearLogin();
  showView('login');
}

// -- GUEST HANDLING LOGIC START --
function requireLoginHandler(e, tab = 'login') {
  if (!AUTH.isLoggedIn()) {
    if (e) e.preventDefault();
    showView(tab);
    return false;
  }
  return true;
}
function attachGuestHandlers() {
  ['nav-wallet', 'nav-help', 'nav-profile'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('click', function(e) {
        requireLoginHandler(e, 'login');
      });
    }
  });
  document.querySelectorAll('.menu-grid a').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      requireLoginHandler(e, 'login');
    });
  });
}
// -- GUEST HANDLING LOGIC END --

// Help tab logic
function helpTabHandler() {
  const navHelp = document.getElementById('nav-help');
  const helpContainer = document.getElementById('help-container');
  if (navHelp && helpContainer) {
    navHelp.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('main-ui').style.display = 'none';
      document.getElementById('profile-ui').style.display = 'none';
      document.getElementById('account-ui').style.display = 'none';
      if (document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'none';
      helpContainer.style.display = 'block';
      document.querySelectorAll('.navbar a').forEach(el => el.classList.remove('active'));
      navHelp.classList.add('active');
      if (!helpContainer.dataset.loaded) {
        fetch("https://amazemm.xyz/api/contact_api.php")
          .then(res => res.json())
          .then(data => {
            const list = document.getElementById('contact-list');
            list.innerHTML = "";
            if(data.success && data.contacts && data.contacts.length) {
              data.contacts.forEach(contact => {
                let icon, label, link, linkText;
                if (contact.type === "viber") {
                  icon = '<i class="fab fa-viber"></i>';
                  label = "Viber";
                  link = "viber://chat?number=" + encodeURIComponent(contact.phone.replace(/[^0-9+]/g, ''));
                  linkText = contact.phone;
                } else if (contact.type === "telegram") {
                  icon = '<i class="fab fa-telegram"></i>';
                  label = "Telegram";
                  if (contact.username) {
                    link = "https://t.me/" + contact.username;
                    linkText = "@" + contact.username;
                  } else {
                    link = "https://t.me/" + contact.phone.replace(/[^0-9+]/g, '');
                    linkText = contact.phone;
                  }
                } else {
                  return;
                }
                list.innerHTML += `
                  <li class="contact-item">
                    <span class="contact-icon">${icon}</span>
                    <span class="contact-details">
                      <span class="contact-label">${label}</span><br>
                      <a class="contact-link" href="${link}" target="_blank">${linkText}</a>
                    </span>
                  </li>
                `;
              });
            } else {
              list.innerHTML = `<li class="contact-item"><span class="contact-details">ဆက်သွယ်ရန်အချက်အလက်များ ရယူ၍မရပါ။<br>ကျေးဇူးပြု၍ နောက်မှ ပြန်လည်ကြိုးစားပါ။</span></li>`;
            }
            helpContainer.dataset.loaded = "1";
          })
          .catch(() => {
            const list = document.getElementById('contact-list');
            list.innerHTML = `<li class="contact-item"><span class="contact-details">ဆက်သွယ်ရန်အချက်အလက်များ ရယူ၍မရပါ။<br>ကျေးဇူးပြု၍ နောက်မှ ပြန်လည်ကြိုးစားပါ။</span></li>`;
          });
      }
    });
  }
  var navHome = document.getElementById('nav-home');
  if (navHome) {
    navHome.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('main-ui').style.display = 'block';
      document.getElementById('profile-ui').style.display = 'none';
      document.getElementById('account-ui').style.display = 'none';
      if (document.getElementById('help-container')) document.getElementById('help-container').style.display = 'none';
      if (document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'none';
      document.querySelectorAll('.navbar a').forEach(el => el.classList.remove('active'));
      navHome.classList.add('active');
    });
  }
}

// Initialize event listeners
function initEventListeners() {
  document.getElementById('nav-home').addEventListener('click', function(e) {
    e.preventDefault();
    if (AUTH.isLoggedIn()) {
      showView('home');
    } else {
      showView('login');
    }
  });
  document.getElementById('nav-profile').addEventListener('click', function(e) {
    e.preventDefault();
    if (AUTH.isLoggedIn()) {
      showView('profile');
    } else {
      showView('login');
    }
  });
  document.getElementById('nav-wallet').addEventListener('click', function(e) {
    e.preventDefault();
    if (AUTH.isLoggedIn()) {
      showView('wallet');
    } else {
      showView('login');
    }
  });
  if (document.getElementById('reg-password')) {
    document.getElementById('reg-password').addEventListener('input', handlePasswordInput);
  }
  if (document.getElementById('logout-button')) {
    document.getElementById('logout-button').addEventListener('click', handleLogout);
  }
  if (document.getElementById('change-password-form')) {
    document.getElementById('change-password-form').addEventListener('submit', handleChangePassword);
  }
  // Hide debug panel UI and do not show "Debug Mode Enabled"
  const debugToggle = document.getElementById('debug-toggle');
  const debugPanel = document.getElementById('debug-panel');
  if (debugToggle) debugToggle.style.display = "none";
  if (debugPanel) debugPanel.style.display = "none";
  document.getElementById('tab-login').addEventListener('click', function() {
    showTab('login');
  });
  document.getElementById('tab-register').addEventListener('click', function() {
    showTab('register');
  });
  if (document.getElementById('login-form')) {
    document.getElementById('login-form').addEventListener('submit', handleLogin);
  }
  if (document.getElementById('register-form')) {
    document.getElementById('register-form').addEventListener('submit', handleRegister);
  }
  attachGuestHandlers();
  helpTabHandler();
  const walletBack = document.getElementById('wallet-back');
  if (walletBack) {
    walletBack.addEventListener('click', function(e) {
      e.preventDefault();
      showView('home');
    });
  }
}

// Initialize app
function initApp() {
  debugLog('Initializing application', {});
  initEventListeners();
  document.getElementById('main-ui').style.display = 'block';
  document.getElementById('account-ui').style.display = 'none';
  document.getElementById('profile-ui').style.display = 'none';
  if (document.getElementById('help-container')) document.getElementById('help-container').style.display = 'none';
  if (document.getElementById('wallet-container')) document.getElementById('wallet-container').style.display = 'none';
  loadHomePage();
}

document.addEventListener('DOMContentLoaded', function() {
  if (!document.getElementById('debug-panel')) {
    const debugPanel = document.createElement('div');
    debugPanel.id = 'debug-panel';
    debugPanel.className = 'debug-panel';
    debugPanel.style.display = 'none';
    document.body.appendChild(debugPanel);
  }
  initApp();
}); 