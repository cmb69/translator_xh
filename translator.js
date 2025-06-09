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

document.querySelectorAll("article.translator_translations").forEach(article => {
    if (!(article instanceof HTMLElement)) return;
    initOverview(article);
});

document.querySelectorAll("article.translator_edit").forEach(article => {
    if (!(article instanceof HTMLElement)) return;
    initEditor(article);
});

/** @param {HTMLElement} article */
function initOverview(article) {
    /** @type {NodeListOf<HTMLInputElement>} */
    var checkboxes;
    /** @type {HTMLButtonElement} */
    var selectAllButton;
    /** @type {HTMLButtonElement} */
    var deselectAllButton;
    /** @type {HTMLButtonElement} */
    var editButton;
    /** @type {HTMLButtonElement} */
    var downloadButton;

    checkboxes = /** @type NodeListOf<HTMLInputElement> */
        article.querySelectorAll("input[type=checkbox]");
    const template = article.querySelector(".translator_template");
    if (!(template instanceof HTMLTemplateElement)) return;
    const controls = article.querySelector(".translator_controls");
    if (!(controls instanceof HTMLElement)) return;
    controls.prepend(template.content);
    let element = article.querySelector("button.translator_select_all");
    if (!(element instanceof HTMLButtonElement)) return;
    selectAllButton = element;
    element = article.querySelector("button.translator_deselect_all");
    if (!(element instanceof HTMLButtonElement)) return;
    deselectAllButton = element;
    element = article.querySelector("button.translator_edit");
    if (!(element instanceof HTMLButtonElement)) return;
    editButton = element;
    element = article.querySelector("button.translator_download");
    if (!(element instanceof HTMLButtonElement)) return;
    downloadButton = element;

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("click", () => {
            downloadButton.disabled = !moduleSelected();
        });
    })

    selectAllButton.style.display = "";
    deselectAllButton.style.display = "none";
    selectAllButton.addEventListener("click", () => {
        deSelectModules(true);
    });
    deselectAllButton.addEventListener("click", () => {
        deSelectModules(false);
    });

    const lis = article.querySelectorAll(" li");
    lis.forEach(li => {
        const clone = editButton.cloneNode(true);
        li.appendChild(clone);
        clone.addEventListener("click", () => {
            deSelectModules(false);
            const checkbox = li.querySelector("input[type=checkbox]");
            if (!(checkbox instanceof HTMLInputElement)) return;
            checkbox.checked = true;
        });
    });
    editButton.remove();

    downloadButton.disabled = !moduleSelected();

    function moduleSelected() {
        let result = false;
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                result = true;
                return;
            }
        });
        return result;
    }

    /** @param {boolean} select */
    function deSelectModules(select) {
        checkboxes.forEach(checkbox => {
            checkbox.checked = select;
        });
        selectAllButton.style.display = select ? "none" : "";
        deselectAllButton.style.display = select ? "" : "none";
        downloadButton.disabled = !select;
    }
}

/** @param {HTMLElement} element */
function initEditor(element) {
    element.querySelectorAll("textarea").forEach(textarea => {
        if (textarea.parentElement === null || textarea.parentElement.previousElementSibling === null) return;
        const sibling = textarea.parentElement.previousElementSibling.querySelector("textarea");
        if (sibling === null) return;
        textarea.addEventListener("focus", () => {
            const height = Math.max(textarea.scrollHeight, sibling.scrollHeight);
            textarea.style.height = height + "px";
            sibling.style.height = height + "px";
        });
        textarea.addEventListener("blur", () => {
            textarea.style.height = "";
            sibling.style.height = "";
        });
    });
}
