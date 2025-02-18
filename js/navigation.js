import { getId } from "./cart_functions.js";
await getId().then((id) => {
  sessionStorage.setItem("user_id", id);
});

const navigation = document.querySelector("#navigation");
const loginBtn = document.querySelector("#login-btn");
const logoutBtn = document.querySelector("#logout-btn");
const loginDialog = document.querySelector("#login-dialog");
const loginErrMsgBox = document.querySelector("#err-msg");
const emailInput = document.querySelector('input[name="email"]');
const loginDialogCloseBtn = document.querySelector("#login-dialog-close");
const passInput = document.querySelector('input[name="password"]');

const registerDialog = document.querySelector("#register-dialog");
const registerErrMsgBox = document.querySelector("#register-err-msg");
const registerDialogCloseBtn = document.querySelector("#register-dialog-close");
const themeToggle = document.querySelector("#theme-toggle");
const systemPrefersDark = window.matchMedia(
  "(prefers-color-scheme: dark)"
).matches;

const basketNavBtn = document.querySelector("#nav-basket");
const cartSidebar = document.querySelector("#cart-sidebar");

//handle theme with data attribute on html and local storage to save preference
const root = document.documentElement;

const mobileToggle = document.querySelector("#mobile-toggle");
const hamburgerIcon = document.querySelector(".hamburger-content");
const closeIcon = document.querySelector(".close-content");
const mobileLogo = document.querySelector("#mobile-store-logo a");
const windowW = window.innerWidth;
const mobileLogoW = document.querySelector(
  "#mobile-store-logo img"
).offsetWidth;

mobileToggle.onclick = () => {
  document.body.style.overflow =
    document.body.style.overflow === "hidden" ? "scroll" : "hidden";
  navigation.style.right = navigation.style.right === "0px" ? "-100%" : "0px";
  console.log(mobileLogo.style.right);
  console.log(mobileLogoW);
  console.log(3 * windowW);
  mobileLogo.style.right =
    mobileLogo.style.right === "0px"
      ? `-${3 * windowW - (mobileLogoW + 48)}px`
      : "0px";
  hamburgerIcon.classList.toggle("hidden");
  closeIcon.classList.toggle("hidden");
};

basketNavBtn.addEventListener("click", () => {
  cartSidebar.classList.toggle("hidden");
  cartSidebar.classList.toggle("animate-reverse");
});

const toggleTheme = () => {
  let currentTheme = root.getAttribute("data-theme"),
    newTheme;
  newTheme = currentTheme === "dark" ? "light" : "dark";
  root.setAttribute("data-theme", newTheme);
  localStorage.setItem("preferredTheme", newTheme);
  setLogoVariant(newTheme);
};

// set store logo adequate to contrast on dark/light bg
const setLogoVariant = (theme) => {
  // adjust relative path based on current page
  const isIndex =
    window.location.pathname.includes("index.php") ||
    window.location.pathname === "/phonezone/";
  const storeLogos = document.querySelectorAll("img[alt='Store Logo'");
  let pathPrefix;
  isIndex ? (pathPrefix = "") : (pathPrefix = "../");

  storeLogos.forEach((logo) => {
    logo.setAttribute("src", `${pathPrefix}res/pz_logo_1_${theme}.svg`);
  });
};

const themeOnLoad = () => {
  // see if user preference is stored
  const storedTheme = localStorage.getItem("preferredTheme");
  if (storedTheme === null) {
    //if not follow the system preference,adjust things thjen return
    systemPrefersDark
      ? root.setAttribute("data-theme", "dark")
      : root.setAttribute("data-theme", "light");
  }
  // if yes then use it
  else {
    root.setAttribute("data-theme", storedTheme);
  }
  return root.getAttribute("data-theme");
};

// call above func on page load
setLogoVariant(themeOnLoad());

// wire all the above logic to the theme toggle btn
themeToggle.addEventListener("click", toggleTheme);

// animate in the login dialog on btn click
loginBtn.onclick = (e) => {
  loginDialog.showModal();
};

if (loginDialog) {
  // src: https://www.stefanjudis.com/blog/a-look-at-the-dialog-elements-super-powers/
  // close dialog with a click outside of its boundaries
  loginDialog.onclick = (e) => {
    if (e.target.nodeName === "DIALOG") {
      loginDialog.close();
    }
  };
  //reset login dialog state on close
  loginDialog.onclose = () => {
    loginErrMsgBox.classList.add("hidden");
    emailInput.setAttribute("autocomplete", "off");
  };

  loginDialogCloseBtn.onclick = () => {
    loginDialog.close();
  };
}

logoutBtn.addEventListener("click", () => {
  sessionStorage.clear();
});

//preventing autocomplete suggestions from showing up before input is focused
//because they obscure entire modal content
if (emailInput) {
  emailInput.onfocus = () => {
    emailInput.setAttribute("autocomplete", "email");
  };
}

if (registerDialog) {
  const regImageInput = registerDialog.querySelector(
    "input[name='register-user-image']"
  );
  const regImageInputLabel = registerDialog.querySelector(
    "label[for='register-user-image']"
  );
  const regImageLabelBg = registerDialog.querySelector("#label-bg");
  const regImageLabelDesc = registerDialog.querySelector(
    "#user-img-description"
  );
  const regImageLabelSvg = registerDialog.querySelector(
    "label[for='register-user-image'] svg"
  );

  registerDialog.onclick = (e) => {
    if (e.target.nodeName === "DIALOG") {
      registerDialog.close();
    }
  };
  registerDialog.onclose = () => {
    registerErrMsgBox.classList.add("hidden");
  };
  registerDialogCloseBtn.onclick = () => {
    registerDialog.close();
  };

  regImageInput.addEventListener("change", () => {
    if (regImageInput.files[0] && regImageInput.files[0].name) {
      const reader = new FileReader();

      reader.onload = (e) => {
        const fileUrl = e.target.result;

        regImageLabelDesc.classList.add("hidden");
        regImageInputLabel.classList.remove("grid-rows-2");
        regImageLabelSvg.classList.add("hidden");
        regImageLabelBg.style.backgroundImage = `url(${fileUrl})`;
        regImageLabelBg.classList.remove("opacity-25");
      };
      reader.readAsDataURL(regImageInput.files[0]);
    }
  });
}
