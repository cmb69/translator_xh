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

/* jshint browser:true,strict:implied */

document.querySelectorAll("article.translator_translations").forEach(initOverview);

document.querySelectorAll("article.translator_edit").forEach(initEditor);

/** @param {HTMLElement} article */
function initOverview(article) {
    var /** @type {NodeListOf<HTMLInputElement>} */ checkboxes,
        /** @type {HTMLTemplateElement} */ template,
        /** @type {HTMLElement} */ controls,
        /** @type {HTMLButtonElement} */ selectAllButton,
        /** @type {HTMLButtonElement} */ deselectAllButton,
        /** @type {HTMLButtonElement} */ editButton,
        /** @type {HTMLButtonElement} */ downloadButton,
        /** @type {NodeListOf<HTMLLIElement>} */ lis;

    checkboxes = article.querySelectorAll("input[type=checkbox]");
    template = article.querySelector(".translator_template");
    controls = article.querySelector(".translator_controls");
    controls.prepend(template.content);
    selectAllButton = article.querySelector("button.translator_select_all");
    deselectAllButton = article.querySelector("button.translator_deselect_all");
    editButton = article.querySelector("button.translator_edit");
    downloadButton = article.querySelector("button.translator_download");

    checkboxes.forEach(function (checkbox) {
        checkbox.onclick = function () {
            downloadButton.disabled = !isModuleSelected();
        };
    });

    selectAllButton.style.display = "";
    deselectAllButton.style.display = "none";
    selectAllButton.onclick = deSelectModules.bind(null, true);
    deselectAllButton.onclick = deSelectModules.bind(null, false);

    lis = article.querySelectorAll("li");
    lis.forEach(function (li) {
        var /** @type {HTMLButtonElement} */ clone;

        clone = /** @type {HTMLButtonElement} */ (editButton.cloneNode(true));
        li.appendChild(clone);
        clone.onclick = function () {
            var /** @type {HTMLInputElement} */ checkbox;

            deSelectModules(false);
            checkbox = li.querySelector("input[type=checkbox]");
            checkbox.checked = true;
        };
    });
    editButton.remove();

    downloadButton.disabled = !isModuleSelected();

    function isModuleSelected() {
        return Array.prototype.some.call(checkboxes, isCheckboxChecked);

        /** @param {HTMLInputElement} checkbox */
        function isCheckboxChecked(checkbox) {
            return checkbox.checked;
        }
    }

    /** @param {boolean} select */
    function deSelectModules(select) {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = select;
        });
        selectAllButton.style.display = select ? "none" : "";
        deselectAllButton.style.display = select ? "" : "none";
        downloadButton.disabled = !select;
    }
}

/** @param {HTMLElement} element */
function initEditor(element) {
    var /** @type {HTMLTextAreaElement} */ first;

    element.querySelectorAll(".translator_to textarea").forEach(initTargetTextarea);
    first = element.querySelector(".translator_to textarea");
    first.focus();

    /** @param {HTMLTextAreaElement} textarea */
    function initTargetTextarea(textarea) {
        var /** @type {HTMLTextAreaElement } */ sibling;

        sibling = textarea.parentElement.previousElementSibling.querySelector("textarea");
        textarea.onfocus = function () {
            var /** @type {number} */ height;
            
            height = Math.max(textarea.scrollHeight, sibling.scrollHeight);
            textarea.style.height = (height + 1) + "px";
            sibling.style.height = (height + 1) + "px";
            textarea.select();
        };
        textarea.onblur = function () {
            textarea.style.height = "";
            sibling.style.height = "";
        };
    }
}
