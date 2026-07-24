(function () {
  'use strict';

  var config = window.BEAUTYCORE_APPOINTMENT_NOTIFICATION || {};
  if (!config.ajaxUrl || !config.nonce || !config.storageKey || !window.fetch) {
    return;
  }

  var pollInterval = Math.max(10000, Number(config.pollInterval) || 30000);
  var audioContext = null;
  var audioUnlocked = false;
  var pendingSound = false;
  var polling = false;
  var lastSeenId = getStoredId();

  function getStoredId() {
    try {
      return Math.max(0, Number(window.localStorage.getItem(config.storageKey)) || 0);
    } catch (error) {
      return 0;
    }
  }

  function storeId(id) {
    lastSeenId = Math.max(0, Number(id) || 0);
    try {
      window.localStorage.setItem(config.storageKey, String(lastSeenId));
    } catch (error) {
      // The in-memory value still prevents repeat alerts in this tab.
    }
  }

  function getAudioContext() {
    if (!audioContext) {
      var AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) {
        return null;
      }
      audioContext = new AudioContext();
    }
    return audioContext;
  }

  function playChime() {
    var context = getAudioContext();
    if (!context || context.state !== 'running') {
      pendingSound = true;
      return;
    }

    [0, 0.16].forEach(function (offset, index) {
      var oscillator = context.createOscillator();
      var gain = context.createGain();
      var start = context.currentTime + offset;

      oscillator.type = 'sine';
      oscillator.frequency.value = index === 0 ? 660 : 880;
      gain.gain.setValueAtTime(0.0001, start);
      gain.gain.exponentialRampToValueAtTime(0.13, start + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.38);
      oscillator.connect(gain);
      gain.connect(context.destination);
      oscillator.start(start);
      oscillator.stop(start + 0.4);
    });
    pendingSound = false;
  }

  function unlockAudio() {
    var context = getAudioContext();
    if (!context) {
      return;
    }
    context.resume().then(function () {
      audioUnlocked = true;
      updateSoundControls();
      if (pendingSound) {
        playChime();
      }
    }).catch(function () {
      audioUnlocked = false;
      updateSoundControls();
    });
  }

  function updateSoundControls() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-beautycore-appointment-sound]'), function (button) {
      button.setAttribute('aria-pressed', audioUnlocked ? 'true' : 'false');
      button.innerHTML = '<span class="dashicons dashicons-controls-volumeon" aria-hidden="true"></span>' + (audioUnlocked ? 'Âm thanh đang bật' : 'Bật âm thanh');
    });
  }

  function initSoundControls() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-beautycore-appointment-sound]'), function (button) {
      button.addEventListener('click', function () {
        unlockAudio();
      });
    });
    updateSoundControls();
  }

  function handleLatestId(latestId) {
    latestId = Number(latestId) || 0;
    if (!latestId) {
      return;
    }
    document.dispatchEvent(new CustomEvent('beautycore:appointment-poll', {
      detail: { appointmentId: latestId }
    }));
    if (lastSeenId > latestId) {
      storeId(latestId);
      return;
    }
    if (!lastSeenId) {
      storeId(latestId);
      return;
    }
    if (latestId > lastSeenId) {
      storeId(latestId);
      document.dispatchEvent(new CustomEvent('beautycore:appointment-created', {
        detail: { appointmentId: latestId }
      }));
      if (audioUnlocked) {
        playChime();
      } else {
        pendingSound = true;
      }
    }
  }

  function poll() {
    if (polling || document.hidden) {
      return;
    }
    polling = true;
    var data = new URLSearchParams({
      action: 'beautycore_appointment_notification',
      _ajax_nonce: config.nonce
    });

    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: data.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (response && response.success && response.data) {
          handleLatestId(response.data.latestId);
        }
      })
      .catch(function () {
        // Retry on the next interval without interrupting the admin workflow.
      })
      .finally(function () {
        polling = false;
      });
  }

  function start() {
    initSoundControls();
    document.addEventListener('pointerdown', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        poll();
      }
    });

    poll();
    window.setInterval(poll, pollInterval);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
