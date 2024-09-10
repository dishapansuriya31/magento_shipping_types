define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-rate-service'
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t,
    storage,
    shippingRateService
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping',
            shippingFormTemplate: 'Magento_Checkout/shipping-address/form',
            shippingMethodListTemplate: 'Magento_Checkout/shipping-address/shipping-method-list',
            shippingMethodItemTemplate: 'Magento_Checkout/shipping-address/shipping-method-item',
            imports: {
                countryOptions: '${ $.parentName }.shippingAddress.shipping-address-fieldset.country_id:indexedOptions'
            }
        },
        visible: ko.observable(!quote.isVirtual()),
        errorValidationMessage: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),
        isRLShippingSelected: ko.observable(true),
        getLiftgate: ko.observable(window.checkoutConfig.liftgate),
        getDelivery: ko.observable(window.checkoutConfig.delivery),
        liftgatePrice: 50.00,
        deliveryPrice: 20.00,
        selectedShippingPrice: ko.observable(0.00),

        initialize: function () {
            this._super();

         
            quote.shippingMethod.subscribe(this.checkShippingMethod.bind(this));
            this.getLiftgate.subscribe(this.updateShippingMethod.bind(this));
            this.getDelivery.subscribe(this.updateShippingMethod.bind(this));

            if (!quote.isVirtual()) {
                stepNavigator.registerStep(
                    'shipping',
                    '',
                    $t('Shipping'),
                    this.visible, _.bind(this.navigate, this),
                    this.sortOrder
                );
            }
            checkoutDataResolver.resolveShippingAddress();

            var hasNewAddress = addressList.some(function (address) {
                return address.getType() === 'new-customer-address';
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    this.getPopUp().openModal();
                }
            }.bind(this));

            this.subscribeToCheckboxChanges();
            this.restoreCheckboxState();

            registry.async('checkoutProvider')(function (checkoutProvider) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
                checkoutProvider.on('shippingAddress', function (shippingAddrsData, changes) {
                    var isStreetAddressDeleted = function () {
                        if (!changes || changes.length === 0) {
                            return false;
                        }

                        var change = changes.pop();

                        if (_.isUndefined(change.value) || _.isUndefined(change.oldValue)) {
                            return false;
                        }

                        if (!change.path.startsWith('shippingAddress.street')) {
                            return false;
                        }

                        return change.value.length === 0 && change.oldValue.length > 0;
                    };

                    var isStreetAddressNotEmpty = shippingAddrsData.street && !_.isEmpty(shippingAddrsData.street[0]);

                    if (isStreetAddressNotEmpty || isStreetAddressDeleted()) {
                        checkoutData.setShippingAddressFromData(shippingAddrsData);
                    }
                });
                shippingRatesValidator.initFields('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset');
            });

            return this;
        },

        navigate: function (step) {
            step && step.isVisible(true);
        },

        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',
                        click: this.onClosePopUp.bind(this)
                    }
                ];

                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                this.popUpForm.options.opened = function () {
                    self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        saveNewAddress: function () {
            var addressData,
                newShippingAddress;

            this.source.set('params.invalid', false);
            this.triggerShippingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);
            }
        },

        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed(function () {
            var shippingMethod = quote.shippingMethod();
            return shippingMethod ?
                shippingMethod['carrier_code'] + '_' + shippingMethod['method_code'] :
                null;
        }),

        checkShippingMethod: function (shippingMethod) {
            if (shippingMethod && shippingMethod['carrier_code']) {
                if (shippingMethod['carrier_code'] === 'randl') {
                    this.isRLShippingSelected(true);
                } else {
                    this.isRLShippingSelected(false);
                }
            } else {
                console.error('Shipping method is not defined or missing carrier_code');
                this.isRLShippingSelected(false);
            }
        },
        

        selectShippingMethod: function (shippingMethod) {
           
            if (shippingMethod && shippingMethod['carrier_code'] && shippingMethod['method_code']) {
                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);
                return true;
            } else {
                console.error('Invalid shipping method:', shippingMethod);
                return false;
            }
            
        },
    
        setShippingInformation: function () {
            if (this.validateShippingInformation()) {
                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();

                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                });
                setShippingInformationAction().done(function () {
                    stepNavigator.next();
                });
            }
        },

        validateShippingInformation: function () {
            var isValid = true;

            if (this.isFormInline) {
                isValid = this.source.get('params.invalid') === false;
            }

            if (!isValid) {
                this.errorValidationMessage($t('Please fill in all required fields.'));
            }

            return isValid;
        },

        triggerShippingDataValidateEvent: function () {
            this.source.trigger('data.validate');
        },

        subscribeToCheckboxChanges: function () {
            this.getLiftgate.subscribe(function (liftgate) {
                localStorage.setItem('liftgateState', liftgate);
                this.updateShippingMethod();
            }.bind(this));

            this.getDelivery.subscribe(function (delivery) {
                localStorage.setItem('deliveryState', delivery);
                this.updateShippingMethod();
            }.bind(this));
        },

        restoreCheckboxState: function () {
            var liftgateState = localStorage.getItem('liftgateState');
            var deliveryState = localStorage.getItem('deliveryState');

            if (liftgateState !== null) {
                this.getLiftgate(liftgateState === 'true');
            }
            if (deliveryState !== null) {
                this.getDelivery(deliveryState === 'true');
            }
        },

        updateShippingMethod: function () {
            var self = this;
            var liftgateValue = this.getLiftgate() ? '1' : '0';
            var deliveryValue = this.getDelivery() ? '1' : '0';

            var selectedMethod = quote.shippingMethod();
         
            // console.log(selectedMethod);
            if (!selectedMethod) {
                // console.error('No shipping method selected');
                // alert($t('No shipping method selected.'));
                return;
            }

            $.ajax({
                url: '/shipping/index/customShip',
                type: 'POST',
                dataType: 'json',
                data: {
                    liftgate: liftgateValue,
                    delivery: deliveryValue,
                    selected_shipping_method: selectedMethod['carrier_code'] + '_' + selectedMethod['method_code']
                },
                success: function (response) {
                    if (response.success) {
                        self.selectedShippingPrice(response.new_shipping_price);
                    } else {
                        alert(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error updating shipping method:', error);
                    alert($t('Error updating shipping method. Please try again.'));
                }
            });
        }
    });
});
