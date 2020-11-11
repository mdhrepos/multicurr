/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

const go = ($) => {

	$.widget("custom.iconSelectMenu", $.ui.selectmenu, {

		/**
		 * Override _renderMenu in order to set wrapping CSS class.
		 * @link https://api.jqueryui.com/selectmenu/#method-_renderMenu
		 * @since 2.4.1
		 * @param ul The list element.
		 * @param items The list items.
		 * @private
		 */
		_renderMenu: function (ul, items) {
			var that = this;
			$.each(items, function (index, item) {
				that._renderItemData(ul, item);
			});
			$(ul).parent().addClass('woocommerce-currency-selector-dropdown');
		},

		// Items are the dropdown list elements. Here we add the flags CSS to each.
		_renderItem: function (ul, item) {
			var li = $("<li>");
			var wrapper = $("<div>", {
				"class": "woocommerce-currency-selector-option",
				text: item.label
			});

			$("<span>", {"class": "currency-flag currency-flag-" + item.value.toLowerCase()}).prependTo(wrapper);

			return li.append(wrapper).appendTo(ul);
		},

		// Button is what we see when the dropdown is closed: the currently selected element. Here we adjust its width.
		_resizeButton: function () {
			var width = this.options.width;

			if (!width) {
				width = this.element.show().outerWidth();
				this.element.hide();
			}

			// Increase width to fit the flag
			width += 18;

			this.button.outerWidth(width);
		},

		// ...and here we add the flag to it.
		_drawButton: function () {
			var that = this;
			var selectedOption = this.element.find("option:selected");

			// Associate existing label with the new button
			this.label = $("label[for='" + this.ids.element + "']").attr("for", this.ids.button);
			this._on(this.label, {
				click: function (event) {
					// noinspection JSPotentiallyInvalidUsageOfThis
					this.button.focus();
					event.preventDefault();
				}
			});

			// Hide original select element
			this.element.hide();

			// Create button
			this.button = $("<div>", {
				"class": "ui-widget",
				tabindex: this.options.disabled ? -1 : 0,
				id: this.ids.button,
				role: "combobox",
				"aria-expanded": "false",
				"aria-autocomplete": "list",
				"aria-owns": this.ids.menu,
				"aria-haspopup": "true"
			})
				.insertAfter(this.element);

			$("<span>", {
				"class": "currency-flag currency-flag-" + selectedOption.val().toLowerCase()
			})
				.prependTo(this.button);


			this.buttonText = $("<span>", {
				"class": "ui-selectmenu-text"
			})
				.appendTo(this.button);

			this._setText(this.buttonText, selectedOption.text());
			this._resizeButton();

			this._on(this.button, this._buttonEvents);
			this.button.one("focusin", function () {

				// Delay rendering the menu items until the button receives focus.
				// The menu may have already been rendered via a programmatic open.
				if (!that.menuItems) {
					that._refreshMenu();
				}
			});
			this._hoverable(this.button);
			this._focusable(this.button);
		}

	});
};

module.exports = {
	go
};
