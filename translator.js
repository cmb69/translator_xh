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

/**
 * @param {boolean} select
 */
function deSelectModules(select) {
    checkboxes.forEach(checkbox => {
        checkbox.checked = select;
    });
    selectAllButton.disabled = select;
    deselectAllButton.disabled = !select;
}

function init() {
    checkboxes = document.querySelectorAll("#translator_list input[type=checkbox]");
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            if (this.checked) {
                deselectAllButton.disabled = false;
            } else {
                selectAllButton.disabled = false;
            }
        });
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
