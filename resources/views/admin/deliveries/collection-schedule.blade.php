<!DOCTYPE html>
<html>
<head>
    <title>Collection Schedule</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .schedule-table th,
        .schedule-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .schedule-table th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .customer-name {
            font-weight: bold;
            color: #333;
        }
        .collection-id {
            font-size: 11px;
            color: #666;
        }
        .collection-address {
            font-size: 12px;
            line-height: 1.3;
        }
        .collection-products {
            font-size: 11px;
        }
        .collection-notes {
            background-color: #fff3cd;
            padding: 4px 6px;
            border-radius: 3px;
            font-size: 11px;
            border: 1px solid #ffeeba;
        }
        .total-summary {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
        }
        .collection-status {
            background-color: #ffc107;
            color: #000;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Collection Schedule</h1>
        <p><strong>{{ count($collections) }} collections</strong> - {{ date('l, F j, Y') }}</p>
        <p><em>Active collection list for driver/staff</em></p>
    </div>

    @if(empty($collections))
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>No collections found for the selected items.</h3>
            <p>The requested collection IDs could not be located in the system.</p>
        </div>
    @else
        <table class="schedule-table">
            <thead>
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 20%;">Customer</th>
                    <th style="width: 25%;">Collection Address</th>
                    <th style="width: 20%;">Products</th>
                    <th style="width: 12%;">Contact</th>
                    <th style="width: 8%;">Amount</th>
                    <th style="width: 7%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $collection)
                    <tr>
                        <td>
                            <div class="collection-id">{{ $collection['order_number'] ?? $collection['id'] ?? 'N/A' }}</div>
                            <span class="collection-status">COLLECT</span>
                        </td>
                        <td>
                            <div class="customer-name">{{ $collection['customer_name'] ?? 'N/A' }}</div>
                            @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                                <div class="collection-notes">
                                    <strong>Note:</strong> {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                                </div>
                            @endif
                        </td>
                        <td class="collection-address">
                            @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                                {{ $collection['shipping_address']['first_name'] ?? '' }} {{ $collection['shipping_address']['last_name'] ?? '' }}<br>
                                @if(!empty($collection['shipping_address']['address_1']))
                                    {{ $collection['shipping_address']['address_1'] }}<br>
                                @endif
                                @if(!empty($collection['shipping_address']['address_2']))
                                    {{ $collection['shipping_address']['address_2'] }}<br>
                                @endif
                                @if(!empty($collection['shipping_address']['city']))
                                    {{ $collection['shipping_address']['city'] }}
                                @endif
                                @if(!empty($collection['shipping_address']['postcode']))
                                    <strong>{{ $collection['shipping_address']['postcode'] }}</strong>
                                @endif
                            @elseif(isset($collection['billing_address']) && is_array($collection['billing_address']))
                                {{ $collection['billing_address']['first_name'] ?? '' }} {{ $collection['billing_address']['last_name'] ?? '' }}<br>
                                @if(!empty($collection['billing_address']['address_1']))
                                    {{ $collection['billing_address']['address_1'] }}<br>
                                @endif
                                @if(!empty($collection['billing_address']['address_2']))
                                    {{ $collection['billing_address']['address_2'] }}<br>
                                @endif
                                @if(!empty($collection['billing_address']['city']))
                                    {{ $collection['billing_address']['city'] }}
                                @endif
                                @if(!empty($collection['billing_address']['postcode']))
                                    <strong>{{ $collection['billing_address']['postcode'] }}</strong>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="collection-products">
                            @if(!empty($collection['products']) && is_array($collection['products']))
                                @foreach($collection['products'] as $product)
                                    <div>{{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Unknown Product' }}</div>
                                @endforeach
                            @else
                                <em>No products listed</em>
                            @endif
                        </td>
                        <td style="font-size: 11px;">
                            @if(!empty($collection['billing_address']['phone']))
                                📞 {{ $collection['billing_address']['phone'] }}<br>
                            @endif
                            @if(!empty($collection['customer_email']))
                                ✉️ {{ $collection['customer_email'] }}
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: bold; color: #28a745;">
                            £{{ number_format($collection['total'] ?? 0, 2) }}
                        </td>
                        <td style="text-align: center;">
                            <span style="background-color: {{ isset($collection['status']) && $collection['status'] === 'active' ? '#28a745' : '#ffc107' }}; color: white; padding: 2px 4px; border-radius: 3px; font-size: 10px;">
                                {{ strtoupper($collection['status'] ?? 'PENDING') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-summary">
            <strong>Total Collections: {{ count($collections) }}</strong> | 
            <strong>Total Value: £{{ number_format(collect($collections)->sum('total'), 2) }}</strong>
        </div>
    @endif
</body>
</html>
            padding: 3px 0;
            border-bottom: 1px dotted #ddd;
        }
        .special-notes {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
        }
        .collection-status {
            float: right;
            background-color: #ffc107;
            color: #000;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        @media print {
            body { margin: 0; }
            .collection-item { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Collection Schedule</h1>
        <p><strong>{{ count($collections) }} collections</strong> - {{ date('l, F j, Y') }}</p>
        <p><em>Items to be collected from customers</em></p>
    </div>

    @if(empty($collections))
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>No collections found for the selected items.</h3>
            <p>The requested collection IDs could not be located in the system.</p>
        </div>
    @else
        @foreach($collections as $collection)
            <div class="collection-item">
                <div class="customer-header">
                    <span>{{ $collection['customer_name'] ?? 'Unknown Customer' }}</span>
                    <span class="collection-status">COLLECTION</span>
                    <div style="float: right; font-size: 12px; font-weight: normal;">
                        ID: {{ $collection['order_number'] ?? $collection['id'] ?? 'N/A' }}
                    </div>
                    <div style="clear: both;"></div>
                </div>

                <div class="collection-details">
                    <div class="address-section">
                        <h4 style="margin-top: 0; color: #28a745;">📍 Collection Address</h4>
                        @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                            {{ $collection['shipping_address']['first_name'] ?? '' }} {{ $collection['shipping_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['shipping_address']['address_1']))
                                {{ $collection['shipping_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['address_2']))
                                {{ $collection['shipping_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['city']))
                                {{ $collection['shipping_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['postcode']))
                                <strong>{{ $collection['shipping_address']['postcode'] }}</strong>
                            @endif
                        @elseif(isset($collection['billing_address']) && is_array($collection['billing_address']))
                            {{ $collection['billing_address']['first_name'] ?? '' }} {{ $collection['billing_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['billing_address']['address_1']))
                                {{ $collection['billing_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['address_2']))
                                {{ $collection['billing_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['city']))
                                {{ $collection['billing_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['postcode']))
                                <strong>{{ $collection['billing_address']['postcode'] }}</strong>
                            @endif
                        @else
                            <em>No address available</em>
                        @endif
                    </div>

                    <div class="contact-section">
                        <h4 style="margin-top: 0; color: #28a745;">📞 Contact Information</h4>
                        @if(!empty($collection['customer_email']))
                            <strong>Email:</strong> {{ $collection['customer_email'] }}<br>
                        @endif
                        @if(!empty($collection['billing_address']['phone']))
                            <strong>Phone:</strong> {{ $collection['billing_address']['phone'] }}<br>
                        @endif
                        @if(empty($collection['customer_email']) && empty($collection['billing_address']['phone']))
                            <em>No contact information available</em>
                        @endif
                    </div>
                </div>

                <div class="products-section">
                    <h4 style="margin-top: 0; color: #28a745;">📋 Items to Collect</h4>
                    @if(!empty($collection['products']) && is_array($collection['products']))
                        @foreach($collection['products'] as $product)
                            <div class="product-item">
                                <strong>{{ $product['quantity'] ?? 1 }}x</strong> {{ $product['name'] ?? 'Unknown Product' }}
                                @if(!empty($product['sku']))
                                    <span style="color: #666; font-size: 11px;">({{ $product['sku'] }})</span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="product-item">
                            <em>No products specified for collection</em>
                        </div>
                    @endif
                </div>

                @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                    <div class="special-notes">
                        <h4 style="margin-top: 0; color: #856404;">⚠️ Special Collection Instructions</h4>
                        {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                    </div>
                @endif

                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                    <strong>Collection Date:</strong> {{ $collection['collection_date'] ?? $collection['delivery_date'] ?? 'TBD' }} |
                    <strong>Status:</strong> {{ ucfirst($collection['status'] ?? 'pending') }} |
                    <strong>Frequency:</strong> {{ $collection['frequency'] ?? 'One-time' }}
                </div>
            </div>
        @endforeach
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px;">
        <p>Collection Schedule Generated: {{ date('Y-m-d H:i:s') }}</p>
        <p><em>Please check items carefully and confirm collection with customer</em></p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
