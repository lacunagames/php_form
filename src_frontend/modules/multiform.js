import './multiform.scss';

const regexLetterSpace = /^[a-zA-Z\s]*$/;
const regexEmail = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
const regexNumber = /^[0-9]*$/;

const isInvalidRule = (rule, value) => {
  if (typeof value !== 'string') {
    return false;
  }
  switch (rule) {
    case 'required':
      return value.length === 0;
    case 'name':
      return !regexLetterSpace.test(value);
    case 'email':
      return value.length > 0 && !regexEmail.test(value);
    case 'phone':
      return !regexNumber.test(value);
    default:
      return false;
  }
};

const formMessages = {
  generalError: 'Invalid form input value. Please review and try again.',
  saveSuccess: 'Your data has been successfully saved.',
  required: 'This field is required.',
  name: 'Invalid name. Only letters and space are allowed.',
  email: 'Invalid email address. E.g. abc34@website.com',
  phone: 'Invalid phone number. Only numbers are allowed.',
};

export default class Multiform {
  constructor(formEl) {
    this.formEl = formEl;
    this.formEl.addEventListener('submit', (e) => this.saveForm(e));
    this.formEl.addEventListener('click', (e) => this.handleClick(e));
    this.formEl.addEventListener('focusout', (e) => this.handleFocusout(e));
    this.message = {
      el: document.createElement('div'),
      isShow: false,
      isSuccess: false,
    };
    // Init Fieldset Template
    this.nextSetIndex = 1;
    this.templateString = document.querySelector('.fieldsets').innerHTML.trim();
    // Init Form Message
    this.message.el.classList.add('message', 'error', 'hidden');
    this.message.el.innerHTML = '<p></p>';
    this.message.el.setAttribute('tabindex', -1);
    this.formEl
      .querySelector('.heading')
      .insertAdjacentElement('afterEnd', this.message.el);
  }

  handleClick(e) {
    const buttonEl = e.target.closest('button');
    if (buttonEl?.name !== 'action') {
      return;
    }
    e.preventDefault();
    const action = buttonEl.value.split('-')[0];
    switch (action) {
      case 'remove':
        return this.removeSet(buttonEl.closest('fieldset'));

      case 'add':
        return this.validateForm() && this.addSet();

      case 'save':
        return this.saveForm();

      default:
        return;
    }
  }

  handleFocusout(e) {
    if (e.target.nodeName === 'INPUT') {
      if (this.validateInput(e.target)) {
        this.validateForm(true);
      }
    }
  }

  toggleMessage(text, isShow, isSuccess) {
    if (
      this.message.isShow === isShow &&
      this.message.isSuccess === isSuccess
    ) {
      return;
    }
    this.message.el.classList.toggle('hidden', !isShow);
    this.message.el.classList.toggle('success', !!isSuccess);
    this.message.el.querySelector('p').textContent = text;
    this.message = { ...this.message, isShow, isSuccess };
    if (isShow || isSuccess) {
      setTimeout(() => this.message.el.focus(), 0);
    }
  }

  validateInput(inputEl, hideMessage) {
    const rules = inputEl.dataset.rules.replace(/\s/g, '').split('|');
    let errorEl = inputEl.nextElementSibling;
    const invalidRule = rules.find((rule) =>
      isInvalidRule(rule, inputEl.value)
    );
    if (!hideMessage || !invalidRule) {
      errorEl.textContent = invalidRule ? formMessages[invalidRule] : '';
    }
    return !invalidRule;
  }

  validateForm(hideMessage) {
    const inputEls = this.formEl.querySelectorAll('input');
    let isValidForm = true;
    inputEls.forEach((inputEl) => {
      isValidForm = this.validateInput(inputEl, hideMessage) && isValidForm;
    });
    if (!hideMessage || isValidForm) {
      this.toggleMessage(formMessages.generalError, !isValidForm);
    }
    return isValidForm;
  }

  addSet() {
    const updatedTemplateString = this.templateString
      .replace(/\[0\]/g, `[${this.nextSetIndex}]`)
      .replace(/access\"\> 1/g, `access"> ${this.nextSetIndex + 1}`)
      .replace(/remove\-0/g, `remove-${this.nextSetIndex}`);
    const template = document.createElement('template');
    template.innerHTML = updatedTemplateString;
    this.formEl
      .querySelector('.fieldsets')
      .appendChild(template.content.firstChild);
    this.nextSetIndex++;
  }

  removeSet(setEl) {
    setEl.remove();
    this.validateForm(true);
  }

  async saveForm(e) {
    e?.preventDefault();
    if (!this.validateForm()) {
      return;
    }
    const formData = new FormData(this.formEl);
    formData.append('action', 'save');
    const response = await fetch('?json=true', {
      method: 'POST',
      body: formData,
    });
    const json = await response.json();
    if (json?.message) {
      const isSuccess = json.message.type === 'success';
      this.toggleMessage(json.message.text, true, isSuccess);
      if (isSuccess) {
        this.addSet();
        this.formEl
          .querySelectorAll('.fieldsets fieldset:not(:last-child)')
          .forEach((el) => el.remove());
      }
    }
  }
}
