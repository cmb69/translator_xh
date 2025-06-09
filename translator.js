/**
 * Copyright 2011-2017 Christoph M. Becker
 *
 * This file is part of Translator_XH.
 *
 * Translator_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Translator_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Translator_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

var checkboxes, selectAllButton, deselectAllButton;

var each = Array.prototype.forEach;

/**
 * Selects resp. deselects all modules.
 *
 * @param {Boolean} select Whether to select all modules.
 */
function deSelectModules(select) {
    each.call(checkboxes, function (checkbox) {
        checkbox.checked = select;
    });
    selectAllButton.disabled = select;
    deselectAllButton.disabled = !select;
}

/**
 * Initializes everything.
 */
function init() {
    var form;

    form = document.getElementById("translator_list");
    checkboxes = [];
    each.call(form.elements, function (element) {
        if (element.type == "checkbox") {
            element.addEventListener("change", function () {
                if (this.checked) {
                    deselectAllButton.disabled = false;
                } else {
                    selectAllButton.disabled = false;
                }
            });
            checkboxes.push(element);
        }
    });
    selectAllButton = document.getElementById("translator_select_all");
    deselectAllButton = document.getElementById("translator_deselect_all");
    selectAllButton.style.display = "";
    deselectAllButton.style.display = "";
    selectAllButton.addEventListener("click", function () {
        deSelectModules(true);
    });
    deselectAllButton.addEventListener("click", function () {
        deSelectModules(false);
    });
}

init();
