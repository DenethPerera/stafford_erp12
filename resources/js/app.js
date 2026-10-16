import "./bootstrap";
import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;
import Sortable from "sortablejs";

document.addEventListener("DOMContentLoaded", () => {
    const list = document.getElementById("stages-list");
    if (!list) return;

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    const reorderUrl = list.dataset.reorderUrl;

    if (!csrfToken || !reorderUrl) return;

    new Sortable(list, {
        animation: 150,
        handle: ".ph-dots-six-vertical",
        onEnd: () => {
            const order = [...list.querySelectorAll("li[data-id]")].map(
                (li) => li.dataset.id,
            );

            list.querySelectorAll("li[data-id]").forEach((li, index) => {
                li.querySelector(".order-badge").textContent = index + 1;
            });

            fetch(reorderUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ order }),
            }).catch(() =>
                alert(
                    "Failed to save new order. Please refresh and try again.",
                ),
            );
        },
    });
});
