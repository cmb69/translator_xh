/**
 * JavaScript of Translator_XH.
 *
 * @author  Christoph M. Becker cmbecker69@gmx.de
 * @version SVN: $Id$
 */

if (typeof addEventListener != "undefined") {
    addEventListener("load", function () {
        "use strict";
        var checkboxes, selectAllButton, deselectAllButton;

        /**
         * Selects resp. deselects all modules.
         *
         * @param {Boolean} select Whether to select all modules.
         */
        function deSelectModules(select) {
            var i;

            for (i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = select;
            }
            selectAllButton.disabled = select;
            deselectAllButton.disabled = !select;
        }

        /**
         * Initializes everything.
         */
        function init() {
            var form, elements, i, element, downloadUrl;

            form = document.getElementById("translator_list");
            elements = form.elements;
            checkboxes = [];
            for (i = 0; i < elements.length; i++) {
                element = elements[i];
                if (element.type == "checkbox") {
                    checkboxes.push(element);
                    checkboxes[i].addEventListener("change", function () {
                        if (this.checked) {
                            deselectAllButton.disabled = false;
                        } else {
                            selectAllButton.disabled = false;
                        }
                    });
                }
            }
            selectAllButton = document.getElementById("translator_select_all");
            deselectAllButton = document.getElementById(
                    "translator_deselect_all");
            selectAllButton.parentNode.style.display = "block";
            selectAllButton.addEventListener("click", function () {
                deSelectModules(true);
            }, false);
            deselectAllButton.addEventListener("click", function () {
                deSelectModules(false);
            }, false);
            downloadUrl = document.getElementById("translator_download_link");
            if (downloadUrl) {
                downloadUrl.addEventListener("click", function () {
                    this.select();
                }, false);
            }
        }

        init();

    }, false);
}
