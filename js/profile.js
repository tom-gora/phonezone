import * as dialogFunctions from "./dialog_functions.js";
import { getOrders, getId, getDataForCartInit } from "./cart_functions.js";

// popup for errors and status messages
const msgBox = document.querySelector("#msg-box");
// dialogs
const editDialog = document.querySelector("#edit-dialog");
const delDialog = document.querySelector("#del-dialog");
// trigger buttons
const editDetailsBtn = document.querySelector("#edit-profile-btn");
const delBtn = document.querySelector("#delete-profile-btn");
// close dialog buttons
const editDialogCancelBtn = editDialog.querySelector("#edit-dialog-close");
const delDialogCancelBtn = document.querySelector("#del-dialog-close");
// other modified elements
const editFormLabels = editDialog.querySelectorAll("label");
const labelsRawHtmlArray = Array.from(editFormLabels).map(
  (label) => label.innerHTML
);
const imageInput = editDialog.querySelector("input[name='edit-user-image']");
const imageInputLabel = editDialog.querySelector(
  "label[for='edit-user-image']"
);
const imageLabelBg = editDialog.querySelector("#label-bg");
const imageLabelDesc = editDialog.querySelector("#user-img-description");
const imageLabelSvg = editDialog.querySelector(
  "label[for='edit-user-image'] svg"
);

const ordersHistory = document.querySelector("#profile-purchases-details")
  .children[1];

const orderHistoryWrapper = ordersHistory.querySelector(
  "#order-history-wrapper"
);

// FIX: can't get id from session storage because most of the time that call
// is resolved later and request fails db selects 0 rows for "no_user" as id
// and returns "no_orders" status.
// so function to get id got split into a module export and called additionally
// as start of promise chain.
await getId()
  .then((id) => {
    return getOrders("get_orders", id);
  })
  .then((response) => response.text())
  .then((responseText) => {
    if (responseText === "no_orders") {
      const emptyOrdersMsg = document.createElement("p");
      emptyOrdersMsg.classList.add("py-4", "text-bg-info");
      emptyOrdersMsg.innerText = "You haven't made any purchases yet.";
      ordersHistory.classList.remove("min-h-80", "grow");
      ordersHistory.appendChild(emptyOrdersMsg);
    } else {
      const orders = JSON.parse(responseText);
      orders.forEach((order) => {
        let orderCounter = 1;
        let totalCounter = 0;
        let orderHtml = `<h6 class="font-bold pb-2">Order number: <span class="text-bg-info">${order.order_hash_number}</span></h6><div class="grid w-full md:max-w-screen-sm grid-cols-10 border-b border-b-brand-secondary dark:border-bg-lighter">`;
        order.products.forEach((product) => {
          totalCounter += parseInt(product.product_price);
          orderHtml += `
<p class="col-span-1 font-bold">${orderCounter++}.</p>
<p class="col-span-6">${product.product_name.length > 18 && window.innerWidth < 768 ? product.product_name.substring(0, 15) + "..." : product.product_name}</p>
<p class="col-span-1 text-end pr-2">x${product.product_amount}</p>
<p class="col-span-2 text-end">£${product.product_price}</p>`;
        });
        orderHtml += `</div><div class='flex justify-between md:max-w-screen-sm'><span>Total: </span><span>£${totalCounter}</span></div>`;
        const tempDiv = document.createElement("div");
        tempDiv.classList.add(
          "mb-4",
          "bg-bg-light",
          "dark:bg-bg-darker",
          "pl-8",
          "pr-4",
          "py-2",
          "rounded-sm"
        );
        tempDiv.innerHTML = orderHtml;
        orderHistoryWrapper.appendChild(tempDiv);
      });
    }
  });

editDialog.querySelector("#edit-confirmation-box").remove();

// url query string
const params = new URLSearchParams(window.location.search);
const stat = params.get("status");

// status messages
stat === "userupdated"
  ? ((msgBox.innerText = "Your details have been updated."),
    msgBox.classList.remove("hidden"))
  : null;

editDetailsBtn.addEventListener("click", () => {
  editDialog.showModal();
});

delBtn.addEventListener("click", () => {
  delDialog.showModal();
});

editDialog.onclick = (e) => {
  if (e.target.nodeName === "DIALOG") {
    dialogFunctions.resetFormForEdit(editFormLabels, labelsRawHtmlArray);
    editDialog.close();
  }
};

delDialog.onclick = (e) => {
  if (e.target.nodeName === "DIALOG") {
    delDialog.close();
  }
};
delDialogCancelBtn.addEventListener("click", (e) => {
  e.preventDefault();
  delDialog.close();
});

editDialogCancelBtn.addEventListener("click", (e) => {
  e.preventDefault();
  dialogFunctions.resetFormForEdit(editFormLabels, labelsRawHtmlArray);
  editDialog.close();
});

imageInput.addEventListener("change", () => {
  if (imageInput.files[0] && imageInput.files[0].name) {
    const reader = new FileReader();

    reader.onload = (e) => {
      const fileUrl = e.target.result;

      imageLabelDesc.classList.add("hidden");
      imageInputLabel.classList.remove("grid-rows-2");
      imageLabelSvg.classList.add("hidden");
      imageLabelBg.style.backgroundImage = `url(${fileUrl})`;
      imageLabelBg.classList.remove("opacity-25");
    };
    reader.readAsDataURL(imageInput.files[0]);
  }
});
