/**
 * AntiBully — generic image cropper (jQuery)
 * Config: window.AB_IMAGE_CROP = { uploadUrl, cropUrl, transformUrl, presets }
 */
(function ($) {
  'use strict';

  var cfg = window.AB_IMAGE_CROP || {};

  var activeField = null;
  var activeFieldConfig = null;
  var activeToken = '';
  var cropCoords = null;
  var activeImageWidth = 0;
  var activeImageHeight = 0;
  var transformBusy = false;
  var cropOverlay = null;

  function csrfToken() {
    var el = document.querySelector('input[name="_token"]');
    if (el && el.value) return el.value;
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }

  function getActiveAspect() {
    if (!activeFieldConfig) return 1;
    var w = activeFieldConfig.width || 1;
    var h = activeFieldConfig.height || 1;
    return h > 0 ? w / h : 1;
  }

  function getFieldConfig(root, isLegacy) {
    var type = isLegacy ? 'cover' : (root.getAttribute('data-crop-type') || 'photo');
    var preset = (cfg.presets && cfg.presets[type]) || {};
    var width = parseInt(root.getAttribute('data-crop-width'), 10) || preset.width || 400;
    var height = parseInt(root.getAttribute('data-crop-height'), 10) || preset.height || 400;
    var shape = root.getAttribute('data-crop-shape') || preset.shape || 'square';
    var label = root.getAttribute('data-crop-label') || preset.label || 'รูปภาพ';
    var acceptSizes = preset.accept_sizes || [[width, height]];

    return {
      type: type,
      width: width,
      height: height,
      shape: shape,
      label: label,
      acceptSizes: acceptSizes,
      isLegacy: !!isLegacy
    };
  }

  function fieldSelectors(isLegacy) {
    if (isLegacy) {
      return {
        input: '[data-cover-input]',
        token: '[data-cover-crop-token]',
        preview: '[data-cover-preview]',
        empty: '[data-cover-empty]',
        readyKey: 'coverCropReady'
      };
    }
    return {
      input: '[data-image-crop-input]',
      token: '[data-image-crop-token]',
      preview: '[data-image-crop-preview]',
      empty: '[data-image-crop-empty]',
      readyKey: 'imageCropReady'
    };
  }

  function isDefaultSize(w, h, fieldConfig) {
    if (!fieldConfig) return false;
    if (w === fieldConfig.width && h === fieldConfig.height) return true;
    return (fieldConfig.acceptSizes || []).some(function (pair) {
      return pair[0] === w && pair[1] === h;
    });
  }

  function destroyCropOverlay() {
    if (cropOverlay) {
      cropOverlay.destroy();
      cropOverlay = null;
    }
    $(window).off('resize.abImageCrop scroll.abImageCrop');
    $('#abImageCropModal').off('scroll.abImageCrop');
  }

  function setToolbarVisible(show) {
    var toolbar = document.getElementById('abImageCropToolbar');
    if (!toolbar) return;
    toolbar.hidden = !show;
  }

  function setToolbarBusy(busy) {
    transformBusy = busy;
    $('#abImageCropToolbar [data-image-crop-transform]').prop('disabled', busy);
  }

  function asidePreviewSize(outW, outH) {
    var max = 220;
    var w = max;
    var h = Math.round(w * outH / outW);
    if (h > max) {
      h = max;
      w = Math.round(h * outW / outH);
    }
    return { w: w, h: h };
  }

  function setAsidePreview(url) {
    var el = document.getElementById('abImageCropAsidePreview');
    if (!el) return;
    if (!url) {
      el.innerHTML = '<span class="ab-cover-crop-modal__rect-placeholder">?</span>';
      return;
    }
    el.innerHTML = '<img src="' + url + '" alt="">';
  }

  function updateAsideShape(shape) {
    var el = document.getElementById('abImageCropAsidePreview');
    if (!el || !activeFieldConfig) return;
    el.classList.remove('is-circle', 'is-square');
    if (shape === 'circle') {
      el.classList.add('is-circle');
    } else if (shape === 'square') {
      el.classList.add('is-square');
    }
    el.style.aspectRatio = activeFieldConfig.width + ' / ' + activeFieldConfig.height;
  }

  function updateModalMeta(fieldConfig) {
    if (!fieldConfig) return;
    $('#abImageCropTitle').text('ตัด' + fieldConfig.label);
    $('#abImageCropHint').text(
      'ลากกรอบเพื่อเลือกพื้นที่ — ขนาดเป้าหมาย ' + fieldConfig.width + '×' + fieldConfig.height + ' px'
    );
    $('#abImageCropAsideNote').text(fieldConfig.width + '×' + fieldConfig.height + ' px');
    updateAsideShape(fieldConfig.shape);
  }

  function openModal(fieldRoot, fieldConfig) {
    activeField = fieldRoot;
    activeFieldConfig = fieldConfig;
    activeToken = '';
    cropCoords = null;
    destroyCropOverlay();
    setToolbarVisible(false);
    setToolbarBusy(false);
    updateModalMeta(fieldConfig);
    $('#abImageCropPreviewWrap').html('<p class="ab-cover-crop-modal__placeholder">กำลังโหลด...</p>');
    setAsidePreview('');
    $('#abImageCropSaveBtn').prop('disabled', true);
    $('#abImageCropModal').addClass('is-open').removeAttr('hidden').attr('aria-hidden', 'false');
  }

  function closeModal() {
    destroyCropOverlay();
    setToolbarVisible(false);
    setToolbarBusy(false);
    $('#abImageCropModal').removeClass('is-open').attr('hidden', '').attr('aria-hidden', 'true');
    activeField = null;
    activeFieldConfig = null;
    activeToken = '';
    cropCoords = null;
    activeImageWidth = 0;
    activeImageHeight = 0;
  }

  function showFieldPreview(root, url, isLegacy) {
    var sel = fieldSelectors(isLegacy);
    var preview = root.querySelector(sel.preview);
    if (!preview) return;
    var img = preview.querySelector('img');
    var empty = preview.querySelector(sel.empty);
    if (!img) return;

    if (!url) {
      var fallback = preview.getAttribute('data-existing') || '';
      if (fallback) {
        img.src = fallback;
        img.hidden = false;
        preview.classList.add('has-image');
        if (empty) empty.hidden = true;
        return;
      }
      img.removeAttribute('src');
      img.hidden = true;
      preview.classList.remove('has-image');
      if (empty) empty.hidden = false;
      return;
    }

    img.src = url;
    img.hidden = false;
    preview.classList.add('has-image');
    if (empty) empty.hidden = true;
  }

  function setCropToken(root, token, isLegacy) {
    var sel = fieldSelectors(isLegacy);
    var hidden = root.querySelector(sel.token);
    if (hidden) hidden.value = token || '';
  }

  function applySelection(selection) {
    cropCoords = selection;
    if (selection && selection.width > 0 && selection.height > 0) {
      $('#abImageCropSaveBtn').prop('disabled', false);
      updateAsidePreviewFromSelection();
    } else {
      $('#abImageCropSaveBtn').prop('disabled', true);
    }
  }

  function updateAsidePreviewFromSelection() {
    if (!cropCoords || cropCoords.width < 1 || cropCoords.height < 1 || !activeFieldConfig) return;
    var img = document.getElementById('abImageCropPhoto');
    if (!img || !img.complete) return;

    var size = asidePreviewSize(activeFieldConfig.width, activeFieldConfig.height);
    var canvas = document.createElement('canvas');
    canvas.width = size.w;
    canvas.height = size.h;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.drawImage(
      img,
      cropCoords.x1,
      cropCoords.y1,
      cropCoords.width,
      cropCoords.height,
      0,
      0,
      size.w,
      size.h
    );
    setAsidePreview(canvas.toDataURL('image/jpeg', 0.9));
  }

  function createCropOverlayMarkup() {
    return (
      '<div id="abImageCropOverlay" class="ab-cover-crop-overlay">' +
      '<div class="ab-cover-crop-overlay__shade ab-cover-crop-overlay__shade--top"></div>' +
      '<div class="ab-cover-crop-overlay__shade ab-cover-crop-overlay__shade--right"></div>' +
      '<div class="ab-cover-crop-overlay__shade ab-cover-crop-overlay__shade--bottom"></div>' +
      '<div class="ab-cover-crop-overlay__shade ab-cover-crop-overlay__shade--left"></div>' +
      '<div class="ab-cover-crop-overlay__box">' +
      '<span class="ab-cover-crop-overlay__handle ab-cover-crop-overlay__handle--nw" data-handle="nw" aria-hidden="true"></span>' +
      '<span class="ab-cover-crop-overlay__handle ab-cover-crop-overlay__handle--ne" data-handle="ne" aria-hidden="true"></span>' +
      '<span class="ab-cover-crop-overlay__handle ab-cover-crop-overlay__handle--sw" data-handle="sw" aria-hidden="true"></span>' +
      '<span class="ab-cover-crop-overlay__handle ab-cover-crop-overlay__handle--se" data-handle="se" aria-hidden="true"></span>' +
      '</div></div>'
    );
  }

  function CoverCropOverlay(imgEl, aspect, onChange) {
    this.img = imgEl;
    this.stage = imgEl.parentElement;
    this.overlay = this.stage.querySelector('#abImageCropOverlay');
    this.box = this.overlay.querySelector('.ab-cover-crop-overlay__box');
    this.shades = {
      top: this.overlay.querySelector('.ab-cover-crop-overlay__shade--top'),
      right: this.overlay.querySelector('.ab-cover-crop-overlay__shade--right'),
      bottom: this.overlay.querySelector('.ab-cover-crop-overlay__shade--bottom'),
      left: this.overlay.querySelector('.ab-cover-crop-overlay__shade--left')
    };
    this.aspect = aspect > 0 ? aspect : 1;
    this.onChange = onChange;
    this.dragMode = '';
    this.dragStart = { x: 0, y: 0, selX: 0, selY: 0, selW: 0, selH: 0 };
    this.sel = { x: 0, y: 0, w: 0, h: 0 };
    this.minW = 48;
    this.syncMetrics();
    this.resetSelection();
    this.render();
    this.bindEvents();
  }

  CoverCropOverlay.prototype.syncMetrics = function () {
    this.naturalW = this.img.naturalWidth || activeImageWidth || this.img.width || 0;
    this.naturalH = this.img.naturalHeight || activeImageHeight || this.img.height || 0;
    this.displayW = this.img.clientWidth || this.img.width || 0;
    this.displayH = this.img.clientHeight || this.img.height || 0;
    this.scaleX = this.displayW > 0 ? this.naturalW / this.displayW : 1;
    this.scaleY = this.displayH > 0 ? this.naturalH / this.displayH : 1;
    this.stage.style.width = this.displayW + 'px';
    this.stage.style.height = this.displayH + 'px';
    activeImageWidth = this.naturalW;
    activeImageHeight = this.naturalH;
  };

  CoverCropOverlay.prototype.resetSelection = function () {
    var aspect = this.aspect;
    var maxW = this.displayW;
    var maxH = this.displayH;
    var w = Math.floor(Math.min(maxW, maxH * aspect) * 0.9);
    var h = Math.floor(w / aspect);
    if (h > maxH) {
      h = Math.floor(maxH * 0.9);
      w = Math.floor(h * aspect);
    }
    this.sel.w = Math.max(this.minW, w);
    this.sel.h = Math.max(Math.round(this.minW / aspect), h);
    this.sel.x = Math.max(0, Math.floor((maxW - this.sel.w) / 2));
    this.sel.y = Math.max(0, Math.floor((maxH - this.sel.h) / 2));
    this.clampSelection();
  };

  CoverCropOverlay.prototype.clampSelection = function () {
    var aspect = this.aspect;
    var maxW = this.displayW;
    var maxH = this.displayH;
    var minH = Math.round(this.minW / aspect);

    this.sel.w = Math.max(this.minW, Math.min(this.sel.w, maxW));
    this.sel.h = Math.round(this.sel.w / aspect);
    if (this.sel.h > maxH) {
      this.sel.h = Math.max(minH, maxH);
      this.sel.w = Math.round(this.sel.h * aspect);
      if (this.sel.w > maxW) {
        this.sel.w = maxW;
        this.sel.h = Math.round(this.sel.w / aspect);
      }
    }

    this.sel.x = Math.max(0, Math.min(this.sel.x, maxW - this.sel.w));
    this.sel.y = Math.max(0, Math.min(this.sel.y, maxH - this.sel.h));
  };

  CoverCropOverlay.prototype.selectionToNatural = function () {
    return {
      x1: Math.round(this.sel.x * this.scaleX),
      y1: Math.round(this.sel.y * this.scaleY),
      x2: Math.round((this.sel.x + this.sel.w) * this.scaleX),
      y2: Math.round((this.sel.y + this.sel.h) * this.scaleY),
      width: Math.round(this.sel.w * this.scaleX),
      height: Math.round(this.sel.h * this.scaleY)
    };
  };

  CoverCropOverlay.prototype.render = function () {
    var x = this.sel.x;
    var y = this.sel.y;
    var w = this.sel.w;
    var h = this.sel.h;
    var dw = this.displayW;
    var dh = this.displayH;

    this.box.style.left = x + 'px';
    this.box.style.top = y + 'px';
    this.box.style.width = w + 'px';
    this.box.style.height = h + 'px';

    this.shades.top.style.left = '0';
    this.shades.top.style.top = '0';
    this.shades.top.style.width = dw + 'px';
    this.shades.top.style.height = y + 'px';

    this.shades.left.style.left = '0';
    this.shades.left.style.top = y + 'px';
    this.shades.left.style.width = x + 'px';
    this.shades.left.style.height = h + 'px';

    this.shades.right.style.left = (x + w) + 'px';
    this.shades.right.style.top = y + 'px';
    this.shades.right.style.width = Math.max(0, dw - x - w) + 'px';
    this.shades.right.style.height = h + 'px';

    this.shades.bottom.style.left = '0';
    this.shades.bottom.style.top = (y + h) + 'px';
    this.shades.bottom.style.width = dw + 'px';
    this.shades.bottom.style.height = Math.max(0, dh - y - h) + 'px';

    if (typeof this.onChange === 'function') {
      this.onChange(this.selectionToNatural());
    }
  };

  CoverCropOverlay.prototype.pointerPos = function (event) {
    var rect = this.stage.getBoundingClientRect();
    return {
      x: Math.max(0, Math.min(event.clientX - rect.left, this.displayW)),
      y: Math.max(0, Math.min(event.clientY - rect.top, this.displayH))
    };
  };

  CoverCropOverlay.prototype.onPointerDown = function (event) {
    if (event.button !== 0) return;
    var handle = event.target.getAttribute('data-handle');
    var pos = this.pointerPos(event);
    this.dragMode = handle || 'move';
    this.dragStart = {
      x: pos.x,
      y: pos.y,
      selX: this.sel.x,
      selY: this.sel.y,
      selW: this.sel.w,
      selH: this.sel.h
    };
    event.preventDefault();
  };

  CoverCropOverlay.prototype.onPointerMove = function (event) {
    if (!this.dragMode) return;
    var aspect = this.aspect;
    var pos = this.pointerPos(event);
    var dx = pos.x - this.dragStart.x;
    var dy = pos.y - this.dragStart.y;
    var start = this.dragStart;

    if (this.dragMode === 'move') {
      this.sel.x = start.selX + dx;
      this.sel.y = start.selY + dy;
    } else {
      var anchorX = start.selX + (this.dragMode.indexOf('e') >= 0 ? 0 : start.selW);
      var anchorY = start.selY + (this.dragMode.indexOf('s') >= 0 ? 0 : start.selH);
      var pointerX = pos.x;
      var pointerY = pos.y;

      if (this.dragMode.indexOf('w') >= 0) pointerX = Math.min(pointerX, anchorX - this.minW);
      if (this.dragMode.indexOf('e') >= 0) pointerX = Math.max(pointerX, anchorX + this.minW);

      var rawW = Math.abs(pointerX - anchorX);
      var rawH = Math.abs(pointerY - anchorY);
      var sizeFromW = rawW;
      var sizeFromH = rawH * aspect;
      var width = Math.max(this.minW, Math.min(sizeFromW, sizeFromH || sizeFromW));

      var maxFromAnchorW = this.dragMode.indexOf('w') >= 0 ? anchorX : this.displayW - anchorX;
      var maxFromAnchorH = this.dragMode.indexOf('n') >= 0 ? anchorY : this.displayH - anchorY;
      width = Math.min(width, maxFromAnchorW, maxFromAnchorH * aspect);

      var height = Math.round(width / aspect);

      if (this.dragMode.indexOf('w') >= 0) {
        this.sel.x = anchorX - width;
      } else {
        this.sel.x = anchorX;
      }

      if (this.dragMode.indexOf('n') >= 0) {
        this.sel.y = anchorY - height;
      } else {
        this.sel.y = anchorY;
      }

      this.sel.w = width;
      this.sel.h = height;
    }

    this.clampSelection();
    this.render();
    event.preventDefault();
  };

  CoverCropOverlay.prototype.onPointerUp = function () {
    this.dragMode = '';
  };

  CoverCropOverlay.prototype.refresh = function () {
    this.syncMetrics();
    this.clampSelection();
    this.render();
  };

  CoverCropOverlay.prototype.bindEvents = function () {
    var self = this;
    this._onPointerDown = function (event) { self.onPointerDown(event); };
    this._onPointerMove = function (event) { self.onPointerMove(event); };
    this._onPointerUp = function () { self.onPointerUp(); };
    this._onResize = function () { self.refresh(); };

    this.overlay.addEventListener('mousedown', this._onPointerDown);
    document.addEventListener('mousemove', this._onPointerMove);
    document.addEventListener('mouseup', this._onPointerUp);
    window.addEventListener('resize', this._onResize);
    $(window).on('resize.abImageCrop scroll.abImageCrop', this._onResize);
    $('#abImageCropModal').on(
      'scroll.abImageCrop',
      '.ab-cover-crop-modal__body, .ab-cover-crop-modal__preview-wrap',
      this._onResize
    );
  };

  CoverCropOverlay.prototype.destroy = function () {
    this.overlay.removeEventListener('mousedown', this._onPointerDown);
    document.removeEventListener('mousemove', this._onPointerMove);
    document.removeEventListener('mouseup', this._onPointerUp);
    window.removeEventListener('resize', this._onResize);
    $(window).off('resize.abImageCrop scroll.abImageCrop', this._onResize);
    $('#abImageCropModal').off(
      'scroll.abImageCrop',
      '.ab-cover-crop-modal__body, .ab-cover-crop-modal__preview-wrap',
      this._onResize
    );
  };

  function startCropOverlay($img) {
    var imgEl = $img.get(0);
    if (!imgEl) return;
    destroyCropOverlay();
    cropOverlay = new CoverCropOverlay(imgEl, getActiveAspect(), applySelection);
  }

  function initCropArea(imgUrl, imageWidth, imageHeight) {
    destroyCropOverlay();
    cropCoords = null;
    activeImageWidth = imageWidth || 0;
    activeImageHeight = imageHeight || 0;
    $('#abImageCropSaveBtn').prop('disabled', true);
    setToolbarVisible(false);

    $('#abImageCropPreviewWrap').html(
      '<div id="abImageCropStage" class="ab-cover-crop-modal__stage-inner">' +
      '<img id="abImageCropPhoto" src="' + imgUrl + '" alt="">' +
      createCropOverlayMarkup() +
      '</div>'
    );
    var $img = $('#abImageCropPhoto');

    function onReady() {
      setToolbarVisible(true);
      window.requestAnimationFrame(function () {
        startCropOverlay($img);
      });
    }

    if ($img.get(0).complete && $img.get(0).naturalWidth > 0) {
      onReady();
    } else {
      $img.one('load', onReady).one('error', function () {
        closeModal();
        Swal.fire({ icon: 'error', title: 'โหลดรูปภาพไม่สำเร็จ' });
      });
    }
  }

  function uploadSelectedFile(file, fieldRoot, fieldConfig) {
    if (!file || !cfg.uploadUrl) return;
    openModal(fieldRoot, fieldConfig);

    var fd = new FormData();
    fd.append('_token', csrfToken());
    fd.append('type', fieldConfig.type);
    fd.append('image', file);

    $.ajax({
      url: cfg.uploadUrl,
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(function (res) {
      if (!res || !res.success) {
        closeModal();
        Swal.fire({ icon: 'error', title: (res && res.message) || 'อัปโหลดไม่สำเร็จ' });
        return;
      }
      activeToken = res.token || '';
      activeImageWidth = res.width || 0;
      activeImageHeight = res.height || 0;
      initCropArea(res.preview_url || '', res.width, res.height);
    }).fail(function (xhr) {
      closeModal();
      var msg = 'อัปโหลดไม่สำเร็จ';
      try {
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
      } catch (e) {}
      Swal.fire({ icon: 'error', title: msg });
    });
  }

  function transformImage(action) {
    if (!activeToken || !activeFieldConfig || !cfg.transformUrl || transformBusy) return;

    setToolbarBusy(true);
    destroyCropOverlay();
    cropCoords = null;
    $('#abImageCropSaveBtn').prop('disabled', true);

    $.ajax({
      url: cfg.transformUrl,
      type: 'POST',
      dataType: 'json',
      data: {
        _token: csrfToken(),
        type: activeFieldConfig.type,
        token: activeToken,
        action: action
      }
    }).done(function (res) {
      if (!res || !res.success) {
        Swal.fire({ icon: 'error', title: (res && res.message) || 'ปรับรูปภาพไม่สำเร็จ' });
        return;
      }
      activeImageWidth = res.width || activeImageWidth;
      activeImageHeight = res.height || activeImageHeight;
      initCropArea(res.preview_url || '', res.width, res.height);
    }).fail(function (xhr) {
      var msg = 'ปรับรูปภาพไม่สำเร็จ';
      try {
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
      } catch (e) {}
      Swal.fire({ icon: 'error', title: msg });
    }).always(function () {
      setToolbarBusy(false);
    });
  }

  function saveCrop() {
    if (!activeField || !activeFieldConfig || !activeToken || !cropCoords || !cfg.cropUrl) return;

    if (cropCoords.width < 1 || cropCoords.height < 1) {
      Swal.fire({ icon: 'warning', title: 'กรุณาเลือกพื้นที่ตัดรูป' });
      return;
    }

    $('#abImageCropSaveBtn').prop('disabled', true);

    $.ajax({
      url: cfg.cropUrl,
      type: 'POST',
      dataType: 'json',
      data: {
        _token: csrfToken(),
        type: activeFieldConfig.type,
        token: activeToken,
        x1: Math.round(cropCoords.x1),
        y1: Math.round(cropCoords.y1),
        w1: Math.round(cropCoords.width),
        h1: Math.round(cropCoords.height)
      }
    }).done(function (res) {
      if (!res || !res.success) {
        Swal.fire({ icon: 'error', title: (res && res.message) || 'ตัดรูปไม่สำเร็จ' });
        $('#abImageCropSaveBtn').prop('disabled', false);
        return;
      }

      var isLegacy = activeFieldConfig.isLegacy;
      var sel = fieldSelectors(isLegacy);
      var fileInput = activeField.querySelector(sel.input);
      setCropToken(activeField, res.token || activeToken, isLegacy);
      if (fileInput) fileInput.value = '';
      showFieldPreview(activeField, res.preview_url || '', isLegacy);
      setAsidePreview(res.preview_url || '');
      closeModal();
    }).fail(function (xhr) {
      var msg = 'ตัดรูปไม่สำเร็จ';
      try {
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
      } catch (e) {}
      Swal.fire({ icon: 'error', title: msg });
      $('#abImageCropSaveBtn').prop('disabled', false);
    });
  }

  function readImageSize(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        URL.revokeObjectURL(url);
        resolve({ width: w, height: h });
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        reject(new Error('read failed'));
      };
      img.src = url;
    });
  }

  function initField(root, isLegacy) {
    var fieldConfig = getFieldConfig(root, isLegacy);
    var sel = fieldSelectors(isLegacy);
    var input = root.querySelector(sel.input);
    if (!input || input.dataset[sel.readyKey] === '1') return;
    input.dataset[sel.readyKey] = '1';

    if (isLegacy) {
      input.dataset.coverReady = '1';
    }

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) {
        setCropToken(root, '', isLegacy);
        showFieldPreview(root, '', isLegacy);
        return;
      }
      if (!file.type || file.type.indexOf('image/') !== 0) {
        setCropToken(root, '', isLegacy);
        showFieldPreview(root, '', isLegacy);
        input.value = '';
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์รูปภาพ' });
        return;
      }

      readImageSize(file).then(function (size) {
        if (isDefaultSize(size.width, size.height, fieldConfig)) {
          setCropToken(root, '', isLegacy);
          var reader = new FileReader();
          reader.onload = function (e) {
            showFieldPreview(root, String(e.target.result || ''), isLegacy);
          };
          reader.readAsDataURL(file);
          return;
        }

        uploadSelectedFile(file, root, fieldConfig);
        input.value = '';
      }).catch(function () {
        uploadSelectedFile(file, root, fieldConfig);
        input.value = '';
      });
    });
  }

  function initLegacyFields() {
    document.querySelectorAll('.ab-cover-field').forEach(function (root) {
      if (!root.querySelector('[data-cover-crop-token]')) return;
      initField(root, true);
    });
  }

  function boot() {
    if (!cfg.uploadUrl) return;

    document.querySelectorAll('[data-image-crop-field]').forEach(function (root) {
      initField(root, false);
    });

    initLegacyFields();

    $(document).on('click', '[data-image-crop-close]', function () {
      closeModal();
    });

    $(document).on('click', '[data-image-crop-transform]', function () {
      var action = $(this).attr('data-image-crop-transform');
      if (action) transformImage(action);
    });

    $('#abImageCropSaveBtn').on('click', function () {
      saveCrop();
    });
  }

  $(boot);
})(jQuery);
