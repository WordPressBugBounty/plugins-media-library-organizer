
jQuery(document).ready(function ($) {

  $("#mlo-replace-file-field").on("change", function (e) {
    handleFileSelect(this.files[0]);
  });

  $("#mlo-replace-file-btn").on("click", function (e) {
    e.preventDefault();
    uploadFile();
  });

  $("#mlo-replace-clear-btn").on("click", function (e) {
    e.preventDefault();
    resetFileReplacer();
  });

  const dropArea = document.getElementById("mlo-file-drop-area");

  if (!dropArea) {
    return;
  }

  ["dragenter", "dragover", "dragleave", "drop"].forEach(function (event) {
    dropArea.addEventListener(event, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ["dragenter", "dragover"].forEach(function (event) {
    dropArea.addEventListener(event, highlight, false);
  });

  ["dragleave", "drop"].forEach(function (event) {
    dropArea.addEventListener(event, unhighlight, false);
  });

  function highlight() {
    dropArea.classList.add("drag-active");
  }

  function unhighlight() {
    dropArea.classList.remove("drag-active");
  }

  dropArea.addEventListener("drop", handleDrop, false);

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const file = dt.files[0];
    handleFileSelect(file);
  }

  function resetFileReplacer(error) {
    if (error) {
      $(".mlo-replace-file-error").removeClass("hidden");
      $(".mlo-replace-file-error").text(error);
    } else {
      $(".mlo-replace-file-error").addClass("hidden");
    }

    $("#mlo-replace-file-btn").prop("disabled", true);
    $("#mlo-replace-clear-btn").prop("disabled", true);
    $("#mlo-replace-file-field").val("");
    $(".mlo-replace-file-preview").html("");
    $(".label-text").show();
    $(".mlo-drop-icon").show();
    $(".mlo-supported-text").show();
  }

  function handleFileSelect(file) {
    $(".mlo-replace-file-error").addClass("hidden");

    if (!file) return;

    if (MLOAttachmentEdit.mimeType !== file.type) {
      resetFileReplacer(MLOAttachmentEdit.i18n.mimeTypeError);
      return;
    }

    // Check file size
    if (file.size > MLOAttachmentEdit.maxFileSize) {
      resetFileReplacer(MLOAttachmentEdit.i18n.maxFileSizeError);
      return;
    }

    // Set the file in the input
    if (file instanceof File) {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      document.getElementById("mlo-replace-file-field").files = dataTransfer.files;
    }

    // Enable button and update UI
    $("#mlo-replace-file-btn").prop("disabled", false);
    $("#mlo-replace-clear-btn").prop("disabled", false);
    $(".label-text").hide();
    $(".mlo-drop-icon").hide();
    $(".mlo-supported-text").hide();

    const type = file.type;
    const showPreview = type.startsWith("image/");
    let html = showPreview ? "<img src='" + URL.createObjectURL(file) + "' />" : "";
    html += "<span>" + file.name + "</span>";

    $(".mlo-replace-file-preview").html(html);
  }

  function uploadFile() {
    const formData = new FormData();
    formData.append("action", "mlo_replace_file");
    formData.append("attachment_id", MLOAttachmentEdit.attachmentId);
    formData.append("file", $("#mlo-replace-file-field")[0].files[0]);
    formData.append("nonce", MLOAttachmentEdit.nonce);

    $(".mlo-svg-loader").show();

    jQuery.ajax({
      url: MLOAttachmentEdit.ajaxURL,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $(".mlo-svg-loader").hide();
        if (response.success) {
          window.location.reload();
        } else {
          $(".mlo-replace-file-error").removeClass("hidden");
          $(".mlo-replace-file-error").text(response.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $(".mlo-svg-loader").hide();
        resetFileReplacer(jqXHR.responseJSON ? jqXHR.responseJSON.message : MLOAttachmentEdit.i18n.replaceFileError);
      }
    });
  }
});
