import "./styles/core.scss";
import MultiForm from "./modules/multiform";

(function () {
  const modules = [];
  const multiFormEls = document.querySelectorAll(".multiform");

  multiFormEls.forEach((formEl) => modules.push(new MultiForm(formEl)));
})();
