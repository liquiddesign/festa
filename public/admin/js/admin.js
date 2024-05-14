function filemanager(field_name, url, type, win) {
    console.log('here');
    let e = tinymce.activeEditor;
    let t = field_name;
    let a = type;
    let s = win;

    let r = window.innerWidth - 30, g = window.innerHeight - 60;

    if (r > 1800 && (r = 1800), g > 1200 && (g = 1200), r > 600) {
        let d = (r - 20) % 138;
        r = r - d + 10
    }
    urltype = 2, "image" == a && (urltype = 1), "media" == a && (urltype = 3);

    var o = "RESPONSIVE FileManager";
    "undefined" != typeof e.settings.filemanager_title && e.settings.filemanager_title && (o = e.settings.filemanager_title);
    var l = "key";
    "undefined" != typeof e.settings.filemanager_access_key && e.settings.filemanager_access_key && (l = e.settings.filemanager_access_key);
    var f = "";
    "undefined" != typeof e.settings.filemanager_sort_by && e.settings.filemanager_sort_by && (f = "&sort_by=" + e.settings.filemanager_sort_by);
    var m = "false";
    "undefined" != typeof e.settings.filemanager_descending && e.settings.filemanager_descending && (m = e.settings.filemanager_descending);
    var c = "";
    "undefined" != typeof e.settings.filemanager_subfolder && e.settings.filemanager_subfolder && (c = "&fldr=" + e.settings.filemanager_subfolder);
    var v = "";
    "undefined" != typeof e.settings.filemanager_crossdomain && e.settings.filemanager_crossdomain && (v = "&crossdomain=1", window.addEventListener ? window.addEventListener("message", n, !1) : window.attachEvent("onmessage", n)),
        tinymce.activeEditor.windowManager.open({
            title: o,
            file: e.settings.external_filemanager_path + "dialog.php?type=" + urltype + "&descending=" + m + f + c + v + "&lang=" + e.settings.language + "&akey=" + l,
            width: r,
            height: g,
            resizable: !0,
            maximizable: !0,
            inline: 1
        }, {
            setUrl: function(n) {
                //console.log(t);
                var i = s.document.getElementById(t);
                if (i.value = e.convertURL(n), "createEvent" in document) {
                    var a = document.createEvent("HTMLEvents");
                    a.initEvent("change", !1, !0), i.dispatchEvent(a)
                } else i.fireEvent("onchange")
            }
        })
}

function initTinyMCExconf(selector, conf_override) {
    // Default confifuration for tinymce
    let tiny_default_config = {
        selector: selector,
        schema: 'html5',
        verify_html: false,
        theme: "modern",
        menubar: false,
        branding: false,
        entity_encoding : "named", //vyjmenované entity nahrazuj
        entities : "160,nbsp", //tohle jsou ty vyjmenované entity, jinak nám to maze nbsp z kodu
        remove_script_host : true,
        document_base_url : base_url+"/",
        object_resizing : 'img',
        code_dialog_width: 800,
        code_dialog_height: 450,
        toolbar_items_size: 'small',
        plugins: [
            "responsivefilemanager noneditable preventdelete autolink link image lists charmap print preview hr anchor pagebreak",
            "searchreplace wordcount visualblocks visualchars code ",
            "insertdatetime media nonbreaking table contextmenu colorpicker",
            "template paste textcolor"
        ],
        image_advtab : true,
        relative_urls :false,
        filemanager_crossdomain: true,
        filemanager_title: "Správce souborů",
        external_filemanager_path: pub_url + "/plugins/filemanager/",
        external_plugins: { "filemanager" : pub_url + "/plugins/filemanager/plugin.min.js"},
        file_browser_callback_types: 'file image media',

        toolbar1: "undo redo | styleselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | " +
            "bullist numlist | subscript superscript | forecolor backcolor | table | link unlink anchor removeformat | responsivefilemanager image media" +
            " | hr charmap nonbreaking | copy cut paste pastetext | " +
            "visualblocks visualchars template deleteLayout code ",
        // file_picker_callback: filemanager,

        file_picker_types: 'file image media',
        file_picker_callback: function (cb, value, meta) {
            var width = window.innerWidth - 30;
            var height = window.innerHeight - 60;
            if (width > 1800)width = 1800;
            if (height > 1200)height = 1200;
            if (width > 600) {
                var width_reduce = (width - 20) % 138;
                width = width - width_reduce + 10;
            }
            var urltype = 2;
            if (meta.filetype == 'image') {
                urltype = 1;
            }
            if (meta.filetype == 'media') {
                urltype = 3;
            }
            var title = "RESPONSIVE FileManager";
            if (typeof this.settings.filemanager_title !== "undefined" && this.settings.filemanager_title) {
                title = this.settings.filemanager_title;
            }
            var akey = "key";
            if (typeof this.settings.filemanager_access_key !== "undefined" && this.settings.filemanager_access_key) {
                akey = this.settings.filemanager_access_key;
            }
            var sort_by = "";
            if (typeof this.settings.filemanager_sort_by !== "undefined" && this.settings.filemanager_sort_by) {
                sort_by = "&sort_by=" + this.settings.filemanager_sort_by;
            }
            var descending = "false";
            if (typeof this.settings.filemanager_descending !== "undefined" && this.settings.filemanager_descending) {
                descending = this.settings.filemanager_descending;
            }
            var fldr = "";
            if (typeof this.settings.filemanager_subfolder !== "undefined" && this.settings.filemanager_subfolder) {
                fldr = "&fldr=" + this.settings.filemanager_subfolder;
            }
            var crossdomain = "";
            // if (typeof this.settings.filemanager_crossdomain !== "undefined" && this.settings.filemanager_crossdomain) {
            //     crossdomain = "&crossdomain=1";
            //     if (window.addEventListener) {
            //         window.addEventListener('message', filemanager_onMessage, false);
            //     } else {
            //         window.attachEvent('onmessage', filemanager_onMessage);
            //     }
            // }
            tinymce.activeEditor.windowManager.open({
                title: title,
                file: this.settings.external_filemanager_path + 'dialog.php?type=' + urltype + '&descending=' + descending + sort_by + fldr + crossdomain + '&lang=' + this.settings.language + '&akey=' + akey,
                width: width,
                height: height,
                resizable: true,
                maximizable: true,
                inline: 1
            }, {
                setUrl: function (url) {
                    cb(url);
                }
            });
        },
        language_url: pub_url + '/plugins/tinymce/langs/cs.js',
        content_css: [
            node_url + '/normalize.css/normalize.css',
            node_url + '/@fortawesome/fontawesome-free/css/all.css',
            pub_url + '/admin/css/tinymce.css?'+cssTimestamp,
        ],
        style_formats: [
            {title: 'Nadpis 1', block: 'h1',  attributes : {'class' : ''}},
            {title: 'Nadpis 2', block: 'h2',  attributes : {'class' : ''}},
            {title: 'Nadpis 3', block: 'h3',  attributes : {'class' : ''}},
            {title: 'Nadpis 4', block: 'h4',  attributes : {'class' : ''}},
            {title: 'Nadpis 5', block: 'h5',  attributes : {'class' : ''}}, //vyrazuju z configu, nebudeme pouzivat
            {title: 'Perex', block: 'p',  attributes : {'class' : 'perex'}},
            {title: 'Odstavec [výchozí]', block: 'p',  attributes : {'class' : ''}},
            {title: 'Citace', block: 'p',  attributes : {'class' : 'cite'}},
            {title: 'Citace-medium', block: 'p',  attributes : {'class' : 'cite medium'}},
            {title: 'Citace-great', block: 'p',  attributes : {'class' : 'cite great'}},
            {title: 'Popisek obrázku', block: 'p',  attributes : {'class' : 'img-label'}},
            {title: 'Menší písmo', inline: 'small'},
            {title: 'Highlight--green', inline : 'span', classes : 'highlight--green'},
            {title: 'Highlight--red', inline : 'span', classes : 'highlight--red'},
        ],
        image_class_list: [
            {title: 'Žádný', value: ''},
            {title: 'Non-responsive', value: 'non-responsive'}
        ],
        link_class_list: [
            {title: 'Žádné', value: ''},
            {title: 'Button', value: 'button'},
            {title: 'Shadow hover', value: 'shadow-hover'},
        ],
        table_class_list: [
            {title: 'Žádná', value: ''},
            {title: 'Řádky', value: 'lines'},
        ],

        rel_list: [
            {title: 'Žádné', value: ''},
            {title: 'Lightbox', value: 'lightbox'},
            {title: 'No-follow', value: 'nofollow'},
        ],

        noneditable_leave_contenteditable: true, // Nastavi noneditable elementy
        keep_styles: false, // vyresetuje nastaveny style po stisknuti ENTER
        noneditable_noneditable_class: "mceNonEditable", //comma separated class list
        templates: tinyTemplates, // load tiny tempaltes from DB,
        forced_root_block : 'p',
        remove_trailing_brs: false,
        paste_text_sticky : true,
        paste_as_text: true,
        valid_elements : "@[style],a[href|target],strong/b,em/i,span,br[*],p,*[*]",
        valid_children: '+p[br],+p[span],+p[script]',
        extended_valid_elements: 'span[class|style],iframe[src|style|width|height|scrolling|marginwidth|marginheight|frameborder|allowfullscreen],script[src|async|charset]',
        setup: function (editor) {
            editor.addButton('deleteLayout', {
                icon : 'awesome fas fa-trash-alt',
                tooltip: "Smazat layout",
                onclick: function () {
                    var element = tinymce.activeEditor.selection.getNode();
                    var component = element.closest('.tiny-component');
                    if (component) {
                        component.remove();
                    }
                }
            });
        },
    };

    // Override configuration if needed
    if (conf_override !== undefined){
        for (let propname in conf_override){
            tiny_default_config[propname] = conf_override[propname];
        }
    }

    // Tiny initialization
    tinymce.init(tiny_default_config);
}

initTinyMCExconf('.richedit');
initTinyMCExconf('.richeditSmall', {height: 400, width: 800});
initTinyMCExconf('.richeditBig', {height: 600, width: 800});
