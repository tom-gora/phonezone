const getPathPrefix = () => {
  const location = window.location.pathname;
  let pathPrefix;

  location === "/phonezone/" || location.includes("index.php")
    ? (pathPrefix = "./")
    : (pathPrefix = "../");
  return pathPrefix;
};
export const getProductData = async (client_request, product_id) => {
  const data = {
    client_request: client_request,
    product_id: product_id
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export const deleteCartState = async (client_request, user_id) => {
  const data = {
    client_request: client_request,
    user_id: user_id
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export const saveCartState = async (client_request, user_id, cart_state) => {
  const data = {
    client_request: client_request,
    user_id: user_id,
    cart_state: cart_state
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      // console.log(response.text());
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

const getDataForCartInit = async (client_request, user_id) => {
  const data = {
    client_request: client_request,
    user_id: user_id
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}./scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export const getCartProductHtml = async () => {
  const cartCardUrl = `${getPathPrefix()}html_components/cart_product_card.html`;
  // const cartCardUrl = `./html_components/cart_product_card.html`;
  const response = await fetch(cartCardUrl);
  if (!response.ok) {
    throw new Error("Network response was not ok");
  }
  return response.text();
};

export const updateCartPrices = (
  subtotalValue,
  shippingValue,
  subtotalDomTarget,
  shippingDomTarget,
  subtotalWithShippingDomTarget
) => {
  subtotalDomTarget.innerText = `£${subtotalValue.toFixed(2)}`;
  shippingDomTarget.innerText = `£${shippingValue.toFixed(2)}`;
  subtotalWithShippingDomTarget.innerText = `£${(subtotalValue + shippingValue).toFixed(2)}`;
};

export const getItemsCountTotal = (cartStateJson) => {
  if (cartStateJson === null) {
    return 0;
  }
  if (cartStateJson.products.length === 0) {
    return 0;
  }

  let itemsCountTotal = 0;
  cartStateJson.products.forEach((product) => {
    itemsCountTotal += parseInt(product.product_amount);
  });
  return itemsCountTotal;
};

export const removeItemCompletely = (product_id, cartStateJson) => {
  const newProducts = cartStateJson.products.filter(
    (product) => product.product_id != product_id
  );
  const newCartState = {
    ...cartStateJson,
    products: newProducts
  };
  return newCartState;
};

export const initCart = async (
  shippingCost,
  user_id,
  cartSidebar,
  basketCounter,
  basketCounterPing
) => {
  const client_request = "init_cart";

  const productsWrapper = cartSidebar.querySelector("#cart-products-wrapper");
  try {
    const user_id = sessionStorage.getItem("user_id");
    if (user_id === "no_id") {
      sessionStorage.setItem("cart_contents", "no_cart");
      return;
    }
    const cardHtml = await getCartProductHtml();
    const cartData = await getDataForCartInit(client_request, user_id);
    const parser = new DOMParser();
    const parsedCard = parser.parseFromString(cardHtml, "text/html");
    if (cartData.order_contents === "no_cart") {
      sessionStorage.setItem("cart_contents", "no_cart");
      basketCounter.classList.add("hidden");
      updatePriceBreakdown("no_cart", cartSidebar, shippingCost);
      return (productsWrapper.innerHTML =
        '<p class="text-lg text-bg-info pl-6">Your cart is empty</p>');
    } else {
      const orderContents = JSON.parse(cartData.order_contents);
      basketCounter.classList.remove("hidden");
      basketCounter.innerText = getItemsCountTotal(orderContents);
      sessionStorage.setItem("cart_contents", cartData.order_contents);
      updatePriceBreakdown(
        JSON.stringify(orderContents),
        cartSidebar,
        shippingCost
      );
      let totalItems = 0;

      const productPromises = orderContents.products.map((product) => {
        const productID = product.product_id;
        const client_request = "get_product_for_cart";
        const amount = parseInt(product.product_amount);
        totalItems = +amount;

        return getProductData(client_request, productID)
          .then((productData) => {
            const nextCard = parsedCard
              .querySelector(".cart-card")
              .cloneNode(true);

            // Update the cart card with product details
            nextCard
              .querySelector("img.cart-insert-img")
              .setAttribute(
                "src",
                `${getPathPrefix()}res/phones/${productData.product_img_path}`
              );
            nextCard.querySelector("h2.cart-insert-name").innerText =
              productData.product_name;
            nextCard.querySelector("p.cart-insert-price").innerText =
              `£${productData.product_price}`;
            nextCard.querySelector("input.cart-insert-amount").value = amount;
            nextCard.setAttribute("data-product-id", productID);
            productsWrapper.appendChild(nextCard);

            nextCard
              .querySelector("input.cart-insert-amount")
              .addEventListener("change", (e) => {
                const currentCartState = JSON.parse(
                  sessionStorage.getItem("cart_contents")
                );
                const newAmount = parseInt(e.target.value);
                currentCartState.products.find(
                  (product) => product.product_id === productID
                ).product_amount = newAmount;
                console.log(sessionStorage.getItem("user_id"));
                saveCartState("save_cart_state", user_id, currentCartState);
                sessionStorage.setItem(
                  "cart_contents",
                  JSON.stringify(currentCartState)
                );
                basketCounter.classList.remove("hidden");
                basketCounter.innerText = getItemsCountTotal(currentCartState);
                updatePriceBreakdown(
                  JSON.stringify(currentCartState),
                  cartSidebar,
                  shippingCost
                );
                basketCounterPing.classList.remove("hidden");
                basketCounterPing.classList.add("animate-ping");
                setTimeout(() => {
                  basketCounterPing.classList.add("hidden");
                  basketCounterPing.classList.remove("animate-ping");
                }, 300);

                if (window.location.href.indexOf("profile.php") !== -1) {
                  const modiefiedProductId = e.target
                    .closest(".cart-card")
                    .getAttribute("data-product-id");
                  const inPageProductAmount = document.querySelector(
                    `.profile-cart-view-card[data-product-id="${modiefiedProductId}"] .profile-cart-view-amount`
                  );
                  inPageProductAmount.innerText = `x${newAmount}`;
                }
              });

            nextCard
              .querySelector(".remove-item-btn")
              .addEventListener("click", (e) => {
                if (window.location.href.indexOf("profile.php") !== -1) {
                  const modiefiedProductId = e.target
                    .closest(".cart-card")
                    .getAttribute("data-product-id");
                  const inPageProductCard = document.querySelector(
                    `.profile-cart-view-card[data-product-id="${modiefiedProductId}"]`
                  );
                  inPageProductCard.remove();
                }
                const cardToRemove = e.target.closest(".cart-card");
                const idToRemove = cardToRemove.getAttribute("data-product-id");
                const currentCartState = JSON.parse(
                  sessionStorage.getItem("cart_contents")
                );
                if (currentCartState.products.length === 1) {
                  sessionStorage.setItem("cart_contents", "no_cart");
                  deleteCartState("delete_cart_state", user_id);
                  basketCounter.classList.add("hidden");
                  updatePriceBreakdown("no_cart", cartSidebar, shippingCost);
                  return (productsWrapper.innerHTML =
                    '<p class="text-lg text-bg-info pl-6">Your cart is empty</p>');
                }
                const newCartState = removeItemCompletely(
                  idToRemove,
                  currentCartState
                );
                saveCartState("save_cart_state", user_id, newCartState);
                sessionStorage.setItem(
                  "cart_contents",
                  JSON.stringify(newCartState)
                );
                basketCounter.innerText = getItemsCountTotal(newCartState);
                cardToRemove.remove();
                updatePriceBreakdown(
                  JSON.stringify(newCartState),
                  cartSidebar,
                  shippingCost
                );
              });
          })
          .catch((error) => {
            console.error("Error fetching product data:", error);
          });
      });
    }
  } catch (error) {
    console.error("Error initializing cart:", error);
  }
};

// TODO: EUREKA!!!
// I need to devise PHP response where it takes an array of IDs present in the current state and it returns
// an associative arr of id => price for those ids. No point of fetching all the data for all producs or pushging
// multiple async requests per product
export const updatePriceBreakdown = async (
  cartStateJson,
  cartSidebar,
  shippingCost
) => {
  const subtotalTxt = cartSidebar.querySelector("#insert-subtotal");
  const shippingTxt = cartSidebar.querySelector("#insert-shipping");
  const subtotalWithShipping = cartSidebar.querySelector(
    "#insert-total-with-shipping"
  );
  const checkoutBtn = cartSidebar.querySelector("#checkout-btn");
  if (cartStateJson === null || cartStateJson === "no_cart") {
    subtotalTxt.innerText = "£0";
    shippingTxt.innerText = "£0";
    subtotalWithShipping.innerText = "£0";

    checkoutBtn.setAttribute("inert", "");
    checkoutBtn.classList.add("opacity-50");
    return;
  }
  checkoutBtn.removeAttribute("inert");
  checkoutBtn.classList.remove("opacity-50");
  const cartState = JSON.parse(cartStateJson);
  const product_ids = cartState.products.map((product) => product.product_id);
  let subtotal = 0;

  const data = {
    client_request: "get_product_prices_only",
    product_ids: product_ids
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((response) => {
      const mergedData = cartState.products.map((product) => {
        const productPrice = response.find((productPrice) => {
          return productPrice.product_id === product.product_id;
        });
        product.product_price = productPrice.product_price;
        return product;
      });
      mergedData.forEach((item) => {
        subtotal += item.product_price * item.product_amount;
      });
      shippingTxt.innerText = `£${shippingCost.toFixed(2)}`;
      subtotalTxt.innerText = `£${subtotal.toFixed(2)}`;
      subtotalWithShipping.innerText = `£${(subtotal + shippingCost).toFixed(2)}`;
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export const addProductFromPage = (
  cartSidebar,
  product_id,
  shippingCost,
  basketCounter,
  basketCounterPing,
  productsWrapper
) => {
  const client_request = "get_product_for_cart";
  const user_id = sessionStorage.getItem("user_id");
  if (user_id === "no_id") {
    console.log("No user ID found");
    const loginDialog = document.querySelector("#login-dialog");
    const loginErrBox = loginDialog.querySelector("#err-msg");
    loginErrBox.innerText = "Log in to use the cart";
    console.log(loginErrBox);
    loginErrBox.classList.remove("hidden");
    loginDialog.showModal();
    return;
  }

  let productFound = false;
  let currentCartState = sessionStorage.getItem("cart_contents");
  if (currentCartState !== null && currentCartState !== "no_cart") {
    currentCartState = JSON.parse(currentCartState);
    productFound = false;
    currentCartState.products.forEach((product) => {
      if (product.product_id === parseInt(product_id)) {
        product.product_amount++;
        saveCartState("save_cart_state", user_id, currentCartState);
        sessionStorage.setItem(
          "cart_contents",
          JSON.stringify(currentCartState)
        );

        const inputToUpdate = document.querySelector(
          `.cart-card[data-product-id="${product_id}"] input.cart-insert-amount`
        );
        if (inputToUpdate) {
          inputToUpdate.value = product.product_amount;
          updatePriceBreakdown(
            JSON.stringify(currentCartState),
            cartSidebar,
            shippingCost
          );
        }

        basketCounter.innerText = getItemsCountTotal(currentCartState);
        productFound = true;

        basketCounterPing.classList.remove("hidden");
        basketCounterPing.classList.add("animate-ping");
        setTimeout(() => {
          basketCounterPing.classList.add("hidden");
          basketCounterPing.classList.remove("animate-ping");
        }, 300);
      }
    });
  }
  if (productFound) {
    return;
  }
  const promises = [
    getCartProductHtml(),
    getProductData(client_request, product_id)
  ];

  Promise.all(promises).then(([cardHtml, productData]) => {
    const parser = new DOMParser();
    const parsedCard = parser.parseFromString(cardHtml, "text/html");
    const cartCard = parsedCard.querySelector(".cart-card");
    if (currentCartState === "no_cart") {
      currentCartState = { products: [] };
    }
    const product = productData;
    cartCard
      .querySelector("img.cart-insert-img")
      .setAttribute(
        "src",
        `${getPathPrefix()}res/phones/${product.product_img_path}`
      );
    cartCard.querySelector("h2.cart-insert-name").innerText =
      product.product_name;
    cartCard.querySelector("p.cart-insert-price").innerText =
      product.product_price;

    const newCartDataObject = {
      product_id: product.product_id,
      product_amount: 1
    };

    currentCartState.products.push(newCartDataObject);
    saveCartState("save_cart_state", user_id, currentCartState);
    sessionStorage.setItem("cart_contents", JSON.stringify(currentCartState));
    basketCounter.classList.remove("hidden");
    basketCounter.innerText = getItemsCountTotal(currentCartState);
    basketCounterPing.classList.remove("hidden");
    basketCounterPing.classList.add("animate-ping");
    setTimeout(() => {
      basketCounterPing.classList.add("hidden");
      basketCounterPing.classList.remove("animate-ping");
    }, 300);

    cartCard.setAttribute("data-product-id", product.product_id);
    updatePriceBreakdown(
      JSON.stringify(currentCartState),
      cartSidebar,
      shippingCost
    );

    cartCard
      .querySelector(".remove-item-btn")
      .addEventListener("click", (e) => {
        e.preventDefault();
        const cardToRemove = e.target.closest(".cart-card");
        const idToRemove = cardToRemove.getAttribute("data-product-id");
        const currentCartState = JSON.parse(
          sessionStorage.getItem("cart_contents")
        );
        if (currentCartState.products.length === 1) {
          sessionStorage.setItem("cart_contents", "no_cart");
          deleteCartState("delete_cart_state", user_id);
          basketCounter.classList.add("hidden");
          updatePriceBreakdown("no_cart", cartSidebar, 0);
          return (productsWrapper.innerHTML =
            '<p class="text-lg text-bg-info pl-6">Your cart is empty</p>');
        }
        const newCartState = removeItemCompletely(idToRemove, currentCartState);
        saveCartState("save_cart_state", user_id, newCartState);
        updatePriceBreakdown(
          JSON.stringify(newCartState),
          cartSidebar,
          shippingCost
        );
        sessionStorage.setItem("cart_contents", JSON.stringify(newCartState));
        basketCounter.innerText = getItemsCountTotal(newCartState);

        basketCounterPing.classList.remove("hidden");
        basketCounterPing.classList.add("animate-ping");
        setTimeout(() => {
          basketCounterPing.classList.add("hidden");
          basketCounterPing.classList.remove("animate-ping");
        }, 300);
        cardToRemove.remove();

        basketCounterPing.classList.remove("hidden");
        basketCounterPing.classList.add("animate-ping");
        setTimeout(() => {
          basketCounterPing.classList.add("hidden");
          basketCounterPing.classList.remove("animate-ping");
        }, 300);
      });

    cartCard
      .querySelector("input.cart-insert-amount")
      .addEventListener("change", (e) => {
        const currentCard = e.target.closest(".cart-card");
        const currentCartState = JSON.parse(
          sessionStorage.getItem("cart_contents")
        );
        const newAmount = parseInt(e.target.value);
        const currentID = parseInt(currentCard.getAttribute("data-product-id"));
        currentCartState.products.find(
          (product) => product.product_id === currentID
        ).product_amount = newAmount;
        saveCartState("save_cart_state", user_id, currentCartState);
        sessionStorage.setItem(
          "cart_contents",
          JSON.stringify(currentCartState)
        );
        basketCounter.classList.remove("hidden");
        basketCounter.innerText = getItemsCountTotal(currentCartState);
        updatePriceBreakdown(
          JSON.stringify(currentCartState),
          cartSidebar,
          shippingCost
        );

        basketCounterPing.classList.remove("hidden");
        basketCounterPing.classList.add("animate-ping");
        setTimeout(() => {
          basketCounterPing.classList.add("hidden");
          basketCounterPing.classList.remove("animate-ping");
        }, 300);
      });

    if (productsWrapper.innerText === "Your cart is empty") {
      productsWrapper.innerText = "";
    }
    productsWrapper.appendChild(cartCard);
  });
};

export const setDefaultDate = (input) => {
  const today = new Date();
  const month = String(today.getMonth() + 1).padStart(2, "0");
  const day = String(today.getDate()).padStart(2, "0");
  const year = today.getFullYear();
  const todayStr = `${year}-${month}-${day}`;
  const fiveYrsFutureDateStr = `${year + 5}-${month}-${day}`;
  input.value = todayStr;
  input.setAttribute("min", todayStr);
  input.setAttribute("max", fiveYrsFutureDateStr);
};

export const validateCvvInput = (input) => {
  const min = parseInt(input.getAttribute("min"));
  const max = parseInt(input.getAttribute("max"));
  const value = parseInt(input.value);
  if (value < min || value > max || isNaN(value)) {
    input.setCustomValidity(`Please enter a value between ${min} and ${max}.`);
    input.reportValidity();
    input.value = "";
  } else {
    input.setCustomValidity("");
  }
};

export const storeOrder = async (client_request, userId, orderContents) => {
  const data = {
    client_request: client_request,
    user_id: userId,
    order_contents: orderContents
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json; charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

const getOrders = async (client_request, userId) => {
  const data = {
    client_request: client_request,
    user_id: userId
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = `${getPathPrefix()}/scripts/cart_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response;
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

const getId = async () => {
  const client_request = "get_id";
  const data = {
    client_request: client_request
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };

  const url = `${getPathPrefix()}scripts/user_functionality.php`;
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.text();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export { getId, getOrders, getDataForCartInit };
