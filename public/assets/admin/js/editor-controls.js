window.portalEditorSetup = function(editor) {
    // Preserve intentional blank lines: TinyMCE serializes <p><br></p> and
    // <p></p> to an empty <p></p>, which renders with zero height on the
    // public page and collapses the blank line the owner typed. Fill such
    // paragraphs with &nbsp; in the OUTPUT HTML only (the live editor DOM is
    // untouched). Paragraphs TinyMCE itself marked with data-mce-placeholder
    // are editor-only filler and are dropped from the saved HTML instead, so
    // editor placeholders never leak into content. Only standard <p>&nbsp;
    // markup is saved — no editor-only attributes.
    // Registered on PreInit because editor.serializer does not exist yet
    // inside the setup callback.
    editor.on('PreInit', function() {
        editor.serializer.addNodeFilter('p', function(nodes) {
            nodes.forEach(function(node) {
                if (node.attr('data-mce-placeholder')) {
                    node.remove();
                    return;
                }
                // An empty paragraph is one with no text besides whitespace/
                // nbsp and no elements other than placeholder <br>s. Iterate
                // the direct child chain (not node.walk(), whose callback
                // contract differs across TinyMCE versions and can stop
                // traversal early).
                var hasContent = false;
                for (var child = node.firstChild; child; child = child.next) {
                    if (child.type === 3) {
                        if (/[^\s\u00a0]/.test(child.value || '')) { hasContent = true; break; }
                    } else if (child.name !== 'br') {
                        hasContent = true; break;
                    }
                }
                if (hasContent) return;
                node.empty();
                var text = new tinymce.html.Node('#text', 3);
                text.value = '\u00a0';
                node.append(text);
            });
        });
    });

    // Corner-handle resize writes absolute pixels (e.g. width: 288px;
    // height: 383px). Pixels are only correct for the editor column, so the
    // same image lands at a different share of the narrower public article
    // column (37% in the editor became 41.4% in preview). Normalize to the
    // percentage format the resize presets use: percent width relative to
    // the editor body, height:auto to keep the aspect ratio, and no
    // width/height attributes. Alignment classes and max-width:100% are
    // left untouched.
    editor.on('ObjectResized', function(e) {
        if (!e.target || e.target.nodeName !== 'IMG') return;
        var img = e.target;
        // TinyMCE still writes its final pixel styles after the last
        // ObjectResized fires, so normalize on the next tick instead of
        // synchronously inside the event.
        setTimeout(function() {
            var bodyWidth = editor.getBody().clientWidth;
            if (!bodyWidth) return;
            var pct = Math.round((img.offsetWidth / bodyWidth) * 1000) / 10;
            if (!isFinite(pct) || pct <= 0) return;
            if (pct > 100) pct = 100;
            img.style.width = pct + '%';
            img.style.height = 'auto';
            img.removeAttribute('width');
            img.removeAttribute('height');
            // The serializer prefers the data-mce-style snapshot over the
            // live style attribute; drag leaves that snapshot in pixels.
            if (img.getAttribute('data-mce-style') !== null) {
                editor.dom.setAttrib(img, 'data-mce-style', img.getAttribute('style'));
            }
        }, 0);
    });

    editor.ui.registry.addButton('layarpenuh', {
        text: 'Layar Penuh',
        onAction: function() { editor.execCommand('mceFullScreen'); },
        onSetup: function(api) {
            function update(e) { api.setText(e.state ? 'Keluar Layar Penuh' : 'Layar Penuh'); }
            editor.on('FullscreenStateChanged', update);
            return function() { editor.off('FullscreenStateChanged', update); };
        }
    });
};

window.portalVideoSetup = function(editor) {
    function selectedVideo() {
        var range = editor.selection.getRng();
        if (range.collapsed || range.startContainer !== range.endContainer || range.endOffset !== range.startOffset + 1) return null;
        var node = range.startContainer.childNodes[range.startOffset];
        return node && node.nodeType === 1 && node.matches('.video-embed-wrapper') ? node : null;
    }
    function removeVideo() {
        var video = selectedVideo();
        if (!video) return;
        editor.undoManager.transact(function() {
            var next = video.nextSibling;
            if (!next || next.nodeType !== 1 || next.matches('.video-embed-wrapper')) {
                next = editor.dom.create('p', {}, '<br>');
                editor.dom.insertAfter(next, video);
            }
            editor.dom.remove(video);
            editor.selection.setCursorLocation(next, 0);
        });
        editor.focus();
        editor.nodeChanged();
    }
    editor.ui.registry.addButton('hapusvideo', {
        text: 'Hapus Video',
        onAction: removeVideo,
        onSetup: function(api) {
            function update() { api.setEnabled(!!selectedVideo()); }
            editor.on('NodeChange SelectionChange', update);
            api.setEnabled(false);
            return function() { editor.off('NodeChange SelectionChange', update); };
        }
    });
    editor.on('PreInit', function() {
        editor.parser.addNodeFilter('div', function(nodes) {
            nodes.forEach(function(node) {
                if ((' ' + (node.attr('class') || '') + ' ').indexOf(' video-embed-wrapper ') !== -1) {
                    node.attr('contenteditable', 'false');
                    node.attr('data-mce-contenteditable', 'false');
                }
            });
        });
        editor.serializer.addAttributeFilter('contenteditable', function(nodes) {
            nodes.forEach(function(node) {
                if ((' ' + (node.attr('class') || '') + ' ').indexOf(' video-embed-wrapper ') !== -1) node.attr('contenteditable', null);
            });
        });
    });
    editor.on('mousedown', function(e) {
        var video = editor.dom.getParent(e.target, '.video-embed-wrapper');
        if (!video) return;
        e.preventDefault();
        editor.focus();
        editor.selection.select(video);
        editor.nodeChanged();
    });
    editor.on('keydown', function(e) {
        if ((e.key === 'Delete' || e.key === 'Backspace') && !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey && selectedVideo()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            removeVideo();
        }
    });
};
