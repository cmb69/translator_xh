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

// @ts-check

init();

function init() {
    /** @type {NodeListOf<HTMLInputElement>} */
    var checkboxes;
    /** @type {HTMLButtonElement} */
    var selectAllButton;
    /** @type {HTMLButtonElement} */
    var deselectAllButton;

    checkboxes = /** @type NodeListOf<HTMLInputElement> */
        document.querySelectorAll("#translator_list input[type=checkbox]");
    let element = document.getElementById("translator_select_all");
    if (!(element instanceof HTMLButtonElement)) return;
    selectAllButton = element;
    element = document.getElementById("translator_deselect_all");
    if (!(element instanceof HTMLButtonElement)) return;
    deselectAllButton = element;
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", event => {
            if (!(event.currentTarget instanceof HTMLInputElement)) return;
            if (event.currentTarget.checked) {
                deselectAllButton.disabled = false;
            } else {
                selectAllButton.disabled = false;
            }
        });
    });
    selectAllButton.style.display = "";
    deselectAllButton.style.display = "";
    selectAllButton.addEventListener("click", () => {
        deSelectModules(true);
    });
    deselectAllButton.addEventListener("click", () => {
        deSelectModules(false);
    });

    /** @param {boolean} select */
    function deSelectModules(select) {
        checkboxes.forEach(checkbox => {
            checkbox.checked = select;
        });
        selectAllButton.disabled = select;
        deselectAllButton.disabled = !select;
    }
}
