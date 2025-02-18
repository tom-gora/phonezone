import * as cartFunctions from "./cart_functions.js";

const shippingCost = 20;
const loggedInID = sessionStorage.getItem("user_id");
const cartSidebar = document.querySelector("#cart-sidebar");
const inCartToggle = cartSidebar.querySelector(
  "#cart-sidebar-innner-close-btn"
);
const productsWrapper = cartSidebar.querySelector("#cart-products-wrapper");
const basketCounter = document.querySelector("#basket-counter");
const basketCounterPing = document.querySelector("#basket-counter-ping");
const savedCardState = sessionStorage.getItem("cart_contents");
const checkoutBtn = cartSidebar.querySelector("#checkout-btn");
const paymentWrapper = cartSidebar.querySelector("#payment-wrapper");
const paymentForm = paymentWrapper.querySelector("#payment-form");
const finalOrderReviewBtn = cartSidebar.querySelector("#order-summary-expand");
const orderSummaryWrapper = cartSidebar.querySelector("#order-summary-wrapper");
const paymentGoBackBtn = cartSidebar.querySelector("#payment-go-back-btn");

const paginatedContentWrapper = document.querySelector("#products-grid");
let phoneCards;
if (window.location.href.indexOf("products.php") !== -1) {
  const observer = new MutationObserver((mutations) => {
    let hasMutated = false;
    mutations.forEach((mutation) => {
      mutation.addedNodes.length > 0
        ? (hasMutated = true)
        : (hasMutated = false);
    });

    if (hasMutated) {
      phoneCards = document.querySelectorAll(".phone-card");
      phoneCards.forEach((phoneCard) => {
        const cardBtn = phoneCard.querySelector(".btn-add-to-cart");
        const idToFetch = phoneCard.getAttribute("data-product-id");
        cardBtn.addEventListener("click", () => {
          cartFunctions.addProductFromPage(
            cartSidebar,
            idToFetch,
            shippingCost,
            basketCounter,
            basketCounterPing,
            productsWrapper
          );
        });
        if (savedCardState !== "no_cart") {
          basketCounter.innerText = cartFunctions.getItemsCountTotal(
            JSON.parse(sessionStorage.getItem("cart_contents"))
          );
        }
      });
    }
  });

  observer.observe(paginatedContentWrapper, { childList: true });
} else {
  phoneCards = document.querySelectorAll(".phone-card");
  phoneCards.forEach((phoneCard) => {
    const cardBtn = phoneCard.querySelector(".btn-add-to-cart");
    const idToFetch = phoneCard.getAttribute("data-product-id");
    cardBtn.addEventListener("click", () => {
      cartFunctions.addProductFromPage(
        cartSidebar,
        idToFetch,
        shippingCost,
        basketCounter,
        basketCounterPing,
        productsWrapper
      );
    });

    if (savedCardState !== "no_cart") {
      basketCounter.innerText = cartFunctions.getItemsCountTotal(
        JSON.parse(sessionStorage.getItem("cart_contents"))
      );
    }
  });
}

paymentGoBackBtn.addEventListener("click", () => {
  productsWrapper.classList.remove("hidden");
  paymentWrapper.classList.add("hidden");
  checkoutBtn.style.display = "block";
  finalOrderReviewBtn.classList.add("hidden");
  productsWrapper.classList.remove(
    "animate-fade-down",
    "animate-reverse",
    "animate-duration-300"
  );
});

paymentForm.addEventListener("submit", (e) => {
  e.preventDefault();
  const userId = sessionStorage.getItem("user_id");
  const orderToSave = sessionStorage.getItem("cart_contents");
  const client_request = "store_order";
  cartFunctions
    .storeOrder(client_request, userId, JSON.parse(orderToSave))
    .then((response) => {
      if (response.success === "true") {
        sessionStorage.setItem("cart_contents", "no_cart");
        cartFunctions.deleteCartState("delete_cart_state", loggedInID);
        setTimeout(() => {
          window.location.href = "/phonezone/index.php?status=order_placed";
        }, 100);
      } else {
        window.location.href = "/phonezone/index.php?status=order_failed";
      }
    });
});

finalOrderReviewBtn.addEventListener("click", () => {
  const wrapperChildren = [...orderSummaryWrapper.children];
  wrapperChildren.forEach((child) => {
    child.classList.toggle("hidden");
  });
  orderSummaryWrapper.classList.toggle("animate-fade-down");
  orderSummaryWrapper.classList.toggle("h-0");
  orderSummaryWrapper.classList.toggle("opacity-0");
});

checkoutBtn.addEventListener("click", () => {
  productsWrapper.classList.add(
    "animate-fade-down",
    "animate-reverse",
    "animate-duration-300"
  );
  let summaryHtml = "";
  let summaryIdCounter = 1;
  const currentState = JSON.parse(sessionStorage.getItem("cart_contents"));
  currentState.products.forEach((product) => {
    const name = productsWrapper
      .querySelector(`.cart-card[data-product-id="${product.product_id}"]`)
      .querySelector(".cart-insert-name").textContent;
    const price = productsWrapper
      .querySelector(`.cart-card[data-product-id="${product.product_id}"]`)
      .querySelector(".cart-insert-price").textContent;
    const amount = productsWrapper
      .querySelector(`.cart-card[data-product-id="${product.product_id}"]`)
      .querySelector(".cart-insert-amount").value;
    summaryHtml += `
<p class="col-span-1 font-bold hidden">${summaryIdCounter++}.</p>
<p class="col-span-6 hidden">${name}</p>
<p class="col-span-1 text-end pr-2 hidden">x${amount}</p>
<p class="col-span-2 text-end hidden">${price}</p>`;
  });
  orderSummaryWrapper.innerHTML = summaryHtml;

  setTimeout(() => {
    productsWrapper.classList.add("hidden");
    paymentWrapper.classList.remove("hidden");
    checkoutBtn.style.display = "none";
    finalOrderReviewBtn.classList.remove("hidden");
  }, 400);
});
if (savedCardState === "no_cart") {
  basketCounter.classList.add("hidden");
}

inCartToggle.addEventListener("click", () => {
  inCartToggle.closest("#cart-sidebar").classList.toggle("hidden");
  inCartToggle.closest("#cart-sidebar").classList.toggle("animate-reverse");
  productsWrapper.classList.remove("hidden");
  paymentWrapper.classList.add("hidden");
  checkoutBtn.style.display = "block";
  finalOrderReviewBtn.classList.add("hidden");
  productsWrapper.classList.remove(
    "animate-fade-down",
    "animate-reverse",
    "animate-duration-300"
  );
});

loggedInID !== "no_id"
  ? cartFunctions.initCart(
      shippingCost,
      loggedInID,
      cartSidebar,
      basketCounter,
      basketCounterPing
    )
  : null;

// payments mock section:
const dateInput = document.querySelector("input[type='date']");
const cvvInput = document.querySelector("#cvv-input");
const cvvTooltip = document.querySelector("#cvv-tooltip");
const cvvTriggerBtn = document.querySelector("#cvv-tooltip-trigger");
const min = parseInt(cvvInput.min, 10);
const max = parseInt(cvvInput.max, 10);
cvvTooltip.innerText = `Valid cvv between ${min} and ${max}.`;
cvvTriggerBtn.addEventListener("mouseenter", () => {
  cvvTooltip.classList.remove("hidden");
});
cvvTriggerBtn.addEventListener("mouseleave", () => {
  cvvTooltip.classList.add("hidden");
});
cartFunctions.setDefaultDate(dateInput);

cvvInput.addEventListener("input", (e) => {
  cartFunctions.validateCvvInput(e.target);
});
