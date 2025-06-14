jQuery(document).ready(function ($) {
  // Initialize global variables
  let selectedTerminal = null;
  let paymentIntentId = null;
  let clientSecret = null;
  let processingComplete = false;

  // Add near the beginning of the script
  if (!stripe_terminal_pos.currency_supported) {
    $("#create-payment").prop("disabled", true)
      .attr("title", "Currency not supported by Stripe Terminal");
  }

  // Handle tax enable/disable toggle
  $("#stripe_enable_tax").on("change", function () {
    const $taxRate = $("#stripe_sales_tax");
    const isEnabled = $(this).is(":checked");

    // Update the global tax settings
    stripe_terminal_pos.enable_tax = isEnabled;

    if (isEnabled) {
      $taxRate.prop("disabled", false);
      // Update the tax rate when enabled
      stripe_terminal_pos.sales_tax_rate = parseFloat($taxRate.val()) / 100;
    } else {
      $taxRate.prop("disabled", true);
      // Set tax rate to 0 when disabled
      stripe_terminal_pos.sales_tax_rate = 0;
    }

    // Update cart totals to reflect the new tax status
    updateCartTotals();
  });

  // Add handler for tax rate changes
  $("#stripe_sales_tax").on("change", function () {
    if ($("#stripe_enable_tax").is(":checked")) {
      stripe_terminal_pos.sales_tax_rate = parseFloat($(this).val()) / 100;
      updateCartTotals();
    }
  });

  // Initialize Select2
  $("#product-search").select2({
    placeholder: "Search for products...",
    allowClear: true,
  });

  // Automatically discover terminals when the page loads
  function autoDiscoverTerminals() {
    $("#discover-terminals")
      .text("Auto-discovering terminals...")
      .prop("disabled", true);
    $(".terminal-list").html("");

    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_discover_readers",
        nonce: stripe_terminal_pos.nonce,
      },
      success: function (response) {
        $("#discover-terminals")
          .text("Discover Terminals")
          .prop("disabled", false);

        if (response.success && response.data.length > 0) {
          let readers = response.data;
          readers.forEach(function (reader, index) {
            const readerElement = $(
              '<div class="terminal-item" data-reader-id="' +
                reader.id +
                '">' +
                "<strong>" +
                reader.label +
                "</strong> (" +
                reader.device_type +
                ")<br>" +
                "Status: " +
                reader.status +
                "</div>"
            );

            $(".terminal-list").append(readerElement);

            // Auto-select terminal only if the setting is enabled
            if (stripe_terminal_pos.auto_select_terminal) {
              if (index === 0 || reader.status === "online") {
                setTimeout(function () {
                  readerElement.trigger("click");
                }, 300);

                // If we found an online terminal, no need to check others
                if (reader.status === "online") {
                  return false; // Break the forEach loop
                }
              }
            }
          });
        } else {
          $(".terminal-list").html(
            "<p>No terminals found or error occurred.</p>"
          );
        }
      },
      error: function () {
        $("#discover-terminals")
          .text("Discover Terminals")
          .prop("disabled", false);
        $(".terminal-list").html("<p>Error connecting to server.</p>");
      },
    });
  }

  // Run auto-discovery on page load
  autoDiscoverTerminals();

  // Keep the manual discover button functionality
  $("#discover-terminals").on("click", function () {
    autoDiscoverTerminals();
  });

  // Select a terminal
  $(document).on("click", ".terminal-item", function () {
    $(".terminal-item").removeClass("selected");
    $(this).addClass("selected");
    selectedTerminal = $(this).data("reader-id");

    // Show a message that terminal is selected
    $("#payment-status").html(
      "<p>Terminal selected: <strong>" +
        $(this).find("strong").text() +
        "</strong></p>"
    );

    // Enable create payment if we have products in the cart
    updateCartTotals();
  });

  // 2. PRODUCT CART FUNCTIONALITY
  // Add product to cart
  $("#add-product").on("click", function () {
    const productSelect = $("#product-search");
    const productId = productSelect.val();

    if (!productId) {
      alert("Please select a product first.");
      return;
    }

    const productName = productSelect
      .find("option:selected")
      .text()
      .split(" ($")[0];
    const productPrice = parseFloat(
      productSelect.find("option:selected").data("price")
    );

    // Check if product already exists in the cart
    const existingRow = $(`#product-${productId}`);
    if (existingRow.length > 0) {
      // Increment quantity if product exists
      const qtyInput = existingRow.find(".product-qty");
      qtyInput.val(parseInt(qtyInput.val()) + 1);
      qtyInput.trigger("change");
    } else {
      // Add new row if product doesn't exist
      const newRow = `
        <tr id="product-${productId}" data-product-id="${productId}" data-price="${productPrice.toFixed(2)}">
            <td>${productName}</td>
            <td>
                <span class="product-price">${formatCurrency(productPrice)}</span>
                <input type="hidden" value="${productPrice.toFixed(2)}">
            </td>
            <td>
                <input type="number" class="product-qty" min="1" value="1">
            </td>
            <td class="product-total">${formatCurrency(productPrice)}</td>
            <td><span class="remove-product">✕</span></td>
        </tr>
      `;

      $("#cart-table tbody").append(newRow);
    }

    // Clear the product selection
    productSelect.val("").trigger("change");

    // Update cart totals
    updateCartTotals();
  });

  // Update the quantity change handler
  $(document).on("change", ".product-qty", function () {
    const row = $(this).closest("tr");
    const price = parseFloat(row.data("price"));
    const qty = parseInt($(this).val());
    const total = price * qty;

    row.find(".product-total").text(formatCurrency(total));
    updateCartTotals();
  });

  // Remove product from cart
  $(document).on("click", ".remove-product", function () {
    $(this).closest("tr").remove();
    updateCartTotals();
  });

  // Update the formatCurrency function
  function formatCurrency(amount) {
    return stripe_terminal_pos.currency_symbol + amount.toFixed(2);
  }

  // Calculate cart totals
  function updateCartTotals() {
    let subtotal = 0;

    $("#cart-table tbody tr").each(function () {
        // Extract only numbers from the text, ignoring any currency symbol
        const rowTotal = parseFloat(
            $(this).find(".product-total").text().replace(/[^0-9.-]+/g, '')
        );
        subtotal += rowTotal;
    });

    const enableTax = stripe_terminal_pos.enable_tax;
    const taxRate = enableTax
        ? parseFloat(stripe_terminal_pos.sales_tax_rate)
        : 0;
    const tax = subtotal * taxRate;
    const total = subtotal + tax;

    $("#subtotal").text(formatCurrency(subtotal));

    // Tax calculations only if tax is enabled
    if (enableTax) {
        $("#tax").text(formatCurrency(tax));
    }

    $("#total").text(formatCurrency(total));

    // Update create payment button state
    if (subtotal > 0 && $(".terminal-item.selected").length > 0) {
        $("#create-payment").prop("disabled", false);
    } else {
        $("#create-payment").prop("disabled", true);
    }
  }

  // 3. PAYMENT PROCESSING
  // Update the create-payment click handler
  $("#create-payment").on("click", function () {
    if (!stripe_terminal_pos.currency_supported) {
      alert("Your current WooCommerce currency is not supported by Stripe Terminal. " +
            "Supported currencies: " + stripe_terminal_pos.supported_currencies.join(", ").toUpperCase());
      return;
    }

    if ($(this).prop("disabled")) {
      return;
    }

    const selectedReader = $(".terminal-item.selected");
    if (selectedReader.length === 0) {
      alert("Please select a terminal reader first.");
      return;
    }

    // Get the total amount from our cart
    const amount = parseFloat($("#total").text().replace(/[^0-9.-]+/g, ''));

    if (isNaN(amount) || amount <= 0) {
      alert("Please add products to the cart first.");
      return;
    }

    // Disable all buttons during the full process
    $("#create-payment").text("Processing...").prop("disabled", true);
    $("#discover-terminals").prop("disabled", true);

    // Format product information as a string for the payment description
    let productDetails = "PRODUCT | QTY | PRICE | TOTAL\n";
    productDetails += "--------------------------------\n";

    $("#cart-table tbody tr").each(function () {
      const productName = $(this).find("td:first").text();
      const price = $(this).find(".product-price").val();
      const qty = $(this).find(".product-qty").val();
      const total = parseFloat($(this).find(".product-total").text().replace(/[^0-9.-]+/g, ''));

      // Add each product row to the string using dynamic currency symbol
      productDetails += `${productName} | ${qty} | ${formatCurrency(parseFloat(price))} | ${formatCurrency(total)}\n`;
    });

    // Update summary information with dynamic currency
    productDetails += "--------------------------------\n";
    productDetails += `SUBTOTAL: ${subtotal}\n`;
    if (stripe_terminal_pos.enable_tax) {
        productDetails += `TAX (${(stripe_terminal_pos.sales_tax_rate * 100).toFixed(2)}%): ${tax}\n`;
    }
    productDetails += `TOTAL: ${total}\n`;

    // Get any additional notes
    const additionalNotes = $("<div>").text($("#payment-description").val()).html();

    // Update the productDetails display
    productDetails += "\nNOTES: " + additionalNotes.replace(/[<>]/g, '');

    $("#payment-status").html("<p>Step 1/2: Creating payment intent...</p>");

    // Step 1: Create the payment intent
    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_create_payment_intent",
        nonce: stripe_terminal_pos.nonce,
        amount: amount,
        currency: stripe_terminal_pos.currency.toLowerCase(),
        metadata: {
          description: productDetails,
        },
      },
      success: function (response) {
        if (response.success && response.data) {
          paymentIntentId = response.data.intent_id;
          clientSecret = response.data.client_secret;

          // Show the payment control buttons
          $(".payment-controls").show();
          $("#check-status, #cancel-payment").prop("disabled", false);

          $("#payment-status").html(
            "<p>Step 1/2: Payment intent created successfully!</p>" +
              "<p><strong>Order Summary:</strong></p><pre>" +
              productDetails.replace(/\n/g, "<br>") +
              "</pre>" +
              "<p>Step 2/2: Sending payment to terminal...</p>"
          );

          // Process the payment
          processPaymentOnTerminal(
            selectedReader.data("reader-id"),
            paymentIntentId
          );
        } else {
          handlePaymentError("Error creating payment intent: " + response.data);
        }
      },
      error: function (xhr, status, error) {
        handlePaymentError("Server error while creating payment: " + error);
      },
    });
  });

  // Extract the process payment logic to a reusable function
  function processPaymentOnTerminal(readerId, paymentId) {
    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_process_payment",
        nonce: stripe_terminal_pos.nonce,
        reader_id: readerId,
        payment_intent_id: paymentId,
      },
      success: function (response) {
        if (response.success) {
          $("#payment-status").html(
            "<p>Payment sent to terminal!</p>" +
              "<p>Status: " + response.data.reader_state + "</p>" +
              "<p>Please follow the instructions on the terminal to complete the payment.</p>"
          );

          // Start automatic status checking
          startStatusChecking(paymentId);
        } else {
          handlePaymentError("Error processing payment: " + response.data);
        }
      },
      error: function (xhr, status, error) {
        handlePaymentError("Server error while processing payment: " + error);
      },
    });
  }

  // Error handling helper
  function handlePaymentError(message) {
    $("#payment-status").html('<p class="error">' + message + "</p>");
    $("#create-payment").text("Create Payment").prop("disabled", false);
    $("#discover-terminals").prop("disabled", false);
  }

  // Auto-check payment status every few seconds
  function startStatusChecking(paymentId) {
    let checkCount = 0;
    const statusInterval = setInterval(function () {
      if (processingComplete || checkCount >= 30) {
        // Limit to 30 checks (about 2-3 minutes)
        clearInterval(statusInterval);
        if (checkCount >= 30 && !processingComplete) {
          $("#payment-status").append(
            "<p>Status checking timed out. Please use the Check Status button.</p>"
          );
        }
        return;
      }

      checkCount++;
      checkPaymentStatus(paymentId);
    }, 5000); // Check every 5 seconds
  }

  // Check payment status
  function checkPaymentStatus(paymentId) {
    const cartItems = [];
    $("#cart-table tbody tr").each(function() {
        cartItems.push({
            product_id: $(this).data('product-id'),
            price: $(this).data('price'),
            quantity: parseInt($(this).find('.product-qty').val()),
            total: parseFloat($(this).find(".product-total").text().replace(/[^0-9.-]+/g, ''))
        });
    });

    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_check_payment_status",
        nonce: stripe_terminal_pos.nonce,
        payment_intent_id: paymentId,
        cart_items: JSON.stringify(cartItems),
        tax: parseFloat($("#tax").text().replace(/[^0-9.-]+/g, '')),
        notes: $("#payment-description").val(),
        reader_id: $(".terminal-item.selected").data("reader-id")
      },
      success: function(response) {
        if (response.success) {
          const status = response.data.status;

          if (status === "succeeded") {
            processingComplete = true;
            const orderMessage = response.data.order_id ? 
                `<p>WooCommerce Order Created: #${response.data.order_id}</p>` : '';

            $("#payment-status").html(
              "<p>Payment complete!</p>" +
              "<p>Status: " + status + "</p>" +
              orderMessage
            );

            // Update the payment result display
            $("#payment-result").html(
              "<h3>Payment Successful!</h3>" +
                "<p>Amount: " +
                formatCurrency(response.data.amount) +
                "</p>" +
                "<p>Currency: " +
                response.data.currency.toUpperCase() +
                "</p>" +
                "<p>Status: " +
                status +
                "</p>"
            );

            // Reset for new payment
            $("#create-payment").text("Create Payment").prop("disabled", false);
            $("#discover-terminals").prop("disabled", false);
            $("#check-status").prop("disabled", true);
            $("#cancel-payment").prop("disabled", true);

            // Clear cart for new order
            $("#cart-table tbody").empty();
            updateCartTotals();
          } else if (
            status === "requires_confirmation" ||
            status === "requires_capture" ||
            status === "processing"
          ) {
            $("#payment-status").append(
              "<p>Payment in progress. Status: " + status + "...</p>"
            );
          } else if (status === "canceled") {
            processingComplete = true;
            $("#payment-status").html("<p>Payment was canceled.</p>");
            $("#create-payment").text("Create Payment").prop("disabled", false);
            $("#discover-terminals").prop("disabled", false);
          }
        }
      },
      error: function () {
        // Don't show errors during automatic checking
      },
    });
  }

  // Cancel payment
  $("#cancel-payment").on("click", function () {
    if (!paymentIntentId) {
      alert("No active payment intent.");
      return;
    }

    if (!confirm("Are you sure you want to cancel this payment?")) {
      return;
    }

    $(this).text("Cancelling...").prop("disabled", true);
    $("#payment-status").html(
      "<p>Cancelling payment and clearing terminal...</p>"
    );

    // First, cancel the payment intent on Stripe's servers
    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_cancel_payment",
        nonce: stripe_terminal_pos.nonce,
        payment_intent_id: paymentIntentId,
      },
      success: function (response) {
        if (response.success) {
          // Now clear the terminal display
          clearTerminalDisplay();
        } else {
          $("#cancel-payment").text("Cancel").prop("disabled", false);
          $("#payment-status").html(
            "<div>Error cancelling payment: " + response.data + "</div>"
          );
        }
      },
      error: function () {
        $("#cancel-payment").text("Cancel").prop("disabled", false);
        $("#payment-status").html("<div>Error connecting to server.</div>");
      },
    });
  });

  // New function to clear the terminal display
  function clearTerminalDisplay() {
    const selectedReader = $(".terminal-item.selected");
    if (selectedReader.length === 0) {
      finalizeCancellation();
      return;
    }

    const readerId = selectedReader.data("reader-id");

    $.ajax({
      url: stripe_terminal_pos.ajax_url,
      type: "POST",
      data: {
        action: "stripe_clear_terminal",
        nonce: stripe_terminal_pos.nonce,
        reader_id: readerId,
      },
      success: function (response) {
        if (response.success) {
          $("#payment-status").append(
            "<p>Terminal display cleared successfully.</p>"
          );
        } else {
          $("#payment-status").append(
            "<p>Note: Could not clear terminal display. " +
              "The payment was cancelled, but the terminal may still show the payment screen.</p>"
          );
        }
        finalizeCancellation();
      },
      error: function () {
        $("#payment-status").append(
          "<p>Error while trying to clear terminal display. " +
            "The payment was cancelled, but the terminal may still show the payment screen.</p>"
        );
        finalizeCancellation();
      },
      complete: function () {
        finalizeCancellation();
      },
    });
  }

  // Helper function to finalize the cancellation process
  function finalizeCancellation() {
    $("#cancel-payment").text("Cancel").prop("disabled", false);
    $("#payment-status").html("<div>Payment cancelled</div>");
    $("#payment-result").html("<div>Payment intent was cancelled.</div>");

    // Reset for new payment
    paymentIntentId = null;
    clientSecret = null;
    processingComplete = true;

    // Hide payment controls
    $(".payment-controls").hide();
    
    $("#create-payment").text("Create & Process Payment").prop("disabled", false);
    $("#discover-terminals").prop("disabled", false);
  }

  // Only change the Create Payment button text to make it clear it's an all-in-one action
  $("#create-payment").text("Create & Process Payment");

  // Add click handler for check status button
  $("#check-status").on("click", function() {
    if (!paymentIntentId) {
        alert("No active payment to check.");
        return;
    }
    $(this).prop("disabled", true);
    checkPaymentStatus(paymentIntentId);
    setTimeout(() => $(this).prop("disabled", false), 2000); // Re-enable after 2s
});
});
