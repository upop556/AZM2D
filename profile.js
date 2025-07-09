// profile.js - Handles profile page logic including affiliate/referral info & profile photo upload

// Helper: Format numbers with commas
function profileFormatNumber(x) {
  if (!x && x !== 0) return "0";
  return Number(x).toLocaleString();
}

// Update profile UI (called after fetching profile API)
function updateProfileUI(user) {
  // Basic info
  document.getElementById('profile-name').textContent = user.name || 'N/A';
  document.getElementById('profile-phone').textContent = user.phone || 'N/A';

  // Avatar logic: show profile photo if available, else fallback
  const avatarImg = document.getElementById('profile-avatar-img');
  const avatarIcon = document.getElementById('profile-avatar-icon');
  if (user.profile_photo && user.profile_photo !== "" && user.profile_photo !== "images/default-avatar.png") {
    avatarImg.src = user.profile_photo;
    avatarImg.style.display = 'block';
    avatarIcon.style.display = 'none';
  } else {
    avatarImg.src = "images/default-avatar.png";
    avatarImg.style.display = 'block';
    avatarIcon.style.display = 'none';
  }

  document.getElementById('balance-amount').textContent = profileFormatNumber(user.balance || 0);
  document.getElementById('info-name').textContent = user.name || 'N/A';
  document.getElementById('info-phone').textContent = user.phone || 'N/A';
  document.getElementById('info-agent-code').textContent = user.agent_code || 'N/A';

  // Affiliate/Referral UI
  loadReferralInfo(user);
}

// Profile Photo Upload Logic
function setupProfilePhotoUpload(apiUrl, userToken) {
  const photoInput = document.getElementById("profile-photo-input");
  const avatarImg = document.getElementById("profile-avatar-img");
  if (!photoInput) return;

  photoInput.addEventListener("change", function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Show preview
    const reader = new FileReader();
    reader.onload = function(evt) {
      avatarImg.src = evt.target.result;
    };
    reader.readAsDataURL(file);

    // Upload to backend (FormData)
    const formData = new FormData();
    formData.append("action", "update_profile_photo");
    formData.append("profile_photo", file);

    fetch(apiUrl, {
      method: "POST",
      headers: {
        "Authorization": userToken
      },
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.profile_photo) {
        avatarImg.src = data.profile_photo;
      } else {
        alert(data.message || "ပုံတင်ခြင်း မအောင်မြင်ပါ။");
      }
    })
    .catch(() => {
      alert("ပုံတင်ခြင်း အဆင်မပြေပါ။");
    });
  });
}

// Affiliate/Referral Program Section Logic
function showCopyMsg() {
  var msg = document.getElementById('referral-copy-success');
  if (msg) {
    msg.style.display = 'inline';
    setTimeout(() => msg.style.display = 'none', 1200);
  }
}

// Load referral info after user profile loaded
function loadReferralInfo(user) {
  if (!user) return;
  var code = user.agent_code;
  var bonus = user.referral_bonus || 0;
  var count = user.referral_count || 0;

  var apkBase = "http://bit.ly/45Ovkuk";
  var link = "";

  // If code is missing/empty/null/undefined OR only whitespace, handle gracefully
  if (typeof code !== "string" || !code.trim()) {
    document.getElementById('referral-code').textContent = "မသတ်မှတ်ရသေးပါ";
    document.getElementById('referral-link').textContent = "-";
    if (document.getElementById('copy-referral-btn')) document.getElementById('copy-referral-btn').disabled = true;
    if (document.getElementById('copy-referral-link-btn')) document.getElementById('copy-referral-link-btn').disabled = true;
    if (document.getElementById('share-referral-link-btn')) document.getElementById('share-referral-link-btn').disabled = true;
  } else {
    code = code.trim();
    link = apkBase + "?ref=" + encodeURIComponent(code);

    document.getElementById('referral-code').textContent = code;
    document.getElementById('referral-link').textContent = link;
    if (document.getElementById('copy-referral-btn')) document.getElementById('copy-referral-btn').disabled = false;
    if (document.getElementById('copy-referral-link-btn')) document.getElementById('copy-referral-link-btn').disabled = false;
    if (document.getElementById('share-referral-link-btn')) document.getElementById('share-referral-link-btn').disabled = false;

    // Copy/Share Logic
    var btnCopyCode = document.getElementById('copy-referral-btn');
    var btnCopyLink = document.getElementById('copy-referral-link-btn');
    var btnShareLink = document.getElementById('share-referral-link-btn');
    if (btnCopyCode) btnCopyCode.onclick = function() {
      navigator.clipboard.writeText(code);
      showCopyMsg();
    };
    if (btnCopyLink) btnCopyLink.onclick = function() {
      navigator.clipboard.writeText(link);
      showCopyMsg();
    };
    if (btnShareLink) btnShareLink.onclick = function() {
      if (navigator.share) {
        navigator.share({
          title: "AZM2D Game App",
          text: "AZM2D Game App ကို ဒီ referral link နဲ့ download လုပ်ပါ -",
          url: link
        }).catch(() => {});
      } else {
        navigator.clipboard.writeText(link);
        showCopyMsg();
        alert("Share function မရှိပါ။ Link ကို Copy လုပ်ပြီး ကိုယ်တိုင် Paste လုပ်နိုင်ပါတယ်။");
      }
    };
  }

  document.getElementById('referral-bonus').textContent = profileFormatNumber(bonus);
  document.getElementById('referral-count').textContent = count;
}