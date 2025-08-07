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
    /** @type {NodeListOf<HTMLInputElement>} */
    var checkboxes;
    /** @type {HTMLTemplateElement} */
    var template;
    /** @type {HTMLElement} */
    var controls;
    /** @type {HTMLButtonElement} */
    var selectAllButton;
    /** @type {HTMLButtonElement} */
    var deselectAllButton;
    /** @type {HTMLButtonElement} */
    var editButton;
    /** @type {HTMLButtonElement} */
    var downloadButton;
    /** @type {NodeListOf<HTMLLIElement>} */
    var lis;

    checkboxes = article.querySelectorAll("input[type=checkbox]");
    template = article.querySelector(".translator_template");
    controls = article.querySelector(".translator_controls");
    controls.prepend(template.content);
    selectAllButton = article.querySelector("button.translator_select_all");
    deselectAllButton = article.querySelector("button.translator_deselect_all");
    editButton = article.querySelector("button.translator_edit");
    downloadButton = article.querySelector("button.translator_download");

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener("click", function () {
            downloadButton.disabled = !moduleSelected();
        });
    });

    selectAllButton.style.display = "";
    deselectAllButton.style.display = "none";
    selectAllButton.addEventListener("click", function () {
        deSelectModules(true);
    });
    deselectAllButton.addEventListener("click", function () {
        deSelectModules(false);
    });

    lis = article.querySelectorAll("li");
    lis.forEach(function (li) {
        var clone = editButton.cloneNode(true);
        li.appendChild(clone);
        clone.addEventListener("click", function () {
            /** @type {HTMLInputElement} */
            var checkbox;
            deSelectModules(false);
            checkbox = li.querySelector("input[type=checkbox]");
            checkbox.checked = true;
        });
    });
    editButton.remove();

    downloadButton.disabled = !moduleSelected();

    function moduleSelected() {
        var result = false;
        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                result = true;
                return;
            }
        });
        return result;
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
    /** @type {HTMLTextAreaElement} */
    var first;
    element.querySelectorAll(".translator_to textarea").forEach(function (textarea) {
        /** @type {HTMLTextAreaElement } */
        var sibling;
        if (!(textarea instanceof HTMLTextAreaElement)) return;
        sibling = textarea.parentElement.previousElementSibling.querySelector("textarea");
        textarea.addEventListener("focus", function () {
            var height = Math.max(textarea.scrollHeight, sibling.scrollHeight);
            textarea.style.height = (height + 1) + "px";
            sibling.style.height = (height + 1) + "px";
            textarea.select();
        });
        textarea.addEventListener("blur", function () {
            textarea.style.height = "";
            sibling.style.height = "";
        });
    });
    first = element.querySelector(".translator_to textarea");
    first.focus();
}
