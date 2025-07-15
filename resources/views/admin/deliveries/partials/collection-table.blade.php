{{-- Collection Table Partial --}}

{{-- Action Buttons for Collections --}}
@if(isset($showCollectionActions) && $showCollectionActions)
<div class="mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllCollections">
            <i class="fas fa-check-square"></i> Select All
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllCollections">
            <i class="fas fa-square"></i> Deselect All
        </button>
        <button type="button" class="btn btn-success btn-sm" id="printCollectionScheduleBtn">
            <i class="fas fa-print"></i> Print Schedule
        </button>
        <button type="button" class="btn btn-warning btn-sm" id="printCollectionSlipsBtn">
            <i class="fas fa-tags"></i> Print Collection Slips
        </button>
    </div>
</div>
@endif

<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                @if(isset($type) && ($type === 'delivery' || $type === 'collection'))
                <th>
                    <input type="checkbox" class="form-check-input {{ $type === 'delivery' ? 'select-all-checkbox' : 'select-all-collection-checkbox' }} table-select-all" title="Select all {{ $type === 'delivery' ? 'deliveries' : 'collections' }}" data-type="{{ $type }}">
                </th>
                @endif
                <th>Customer</th>
                <th>Customer Notes</th>
                <th>Address</th>
                <th>Products</th>
                <th>Last Paid</th>
                <th>Contact</th>
                <th>Frequency</th>
                <th>Week</th>
                <th>Status</th>
                <th>Next Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $collection)
                <tr>
                    @if(isset($type) && ($type === 'delivery' || $type === 'collection'))
                    <td>
                        <input type="checkbox" class="form-check-input {{ $type === 'delivery' ? 'delivery-checkbox' : 'collection-checkbox' }}" 
                               value="{{ json_encode($collection) }}"
                               data-delivery-id="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                               data-collection-id="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                               data-order-id="{{ $collection['order_number'] ?? '' }}"
                               data-customer-name="{{ $collection['customer_name'] ?? 'N/A' }}"
                               data-customer-email="{{ $collection['customer_email'] ?? '' }}"
                               data-delivery-date="{{ $collection['delivery_date'] ?? $collection['date'] ?? '' }}"
                               data-customer-notes="{{ $collection['special_instructions'] ?? $collection['delivery_notes'] ?? '' }}"
                               data-address="{{ json_encode($collection['shipping_address'] ?? $collection['billing_address'] ?? []) }}">
                    </td>
                    @endif
                    <td>
                        <strong>{{ $collection['customer_name'] ?? 'N/A' }}</strong>
                        @if(isset($collection['order_number']))
                            <br><small class="text-muted">ID: {{ $collection['order_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        {{-- Customer Notes - Dedicated Column --}}
                        @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                            <div class="customer-notes p-2 bg-warning-subtle border border-warning rounded">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <strong>Note:</strong> {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
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
                                {{ $collection['shipping_address']['postcode'] }}
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
                                {{ $collection['billing_address']['postcode'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['products']) && is_array($collection['products']))
                            @foreach($collection['products'] as $product)
                                <div class="mb-1">
                                    <strong>{{ $product['quantity'] ?? 1 }}x</strong> {{ $product['name'] ?? 'Unknown Product' }}
                                </div>
                            @endforeach
                        @else
                            <small class="text-muted">No products found</small>
                        @endif
                    </td>
                    <td>
                        {{-- Last Paid Amount --}}
                        <strong class="text-success">£{{ number_format($collection['total'] ?? 0, 2) }}</strong>
                        @if(!empty($collection['payment_date']))
                            <br><small class="text-muted">{{ date('M j', strtotime($collection['payment_date'])) }}</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['billing_address']['phone']))
                            <i class="fas fa-phone"></i> {{ $collection['billing_address']['phone'] }}<br>
                        @endif
                        @if(!empty($collection['customer_email']))
                            <i class="fas fa-envelope"></i> {{ $collection['customer_email'] }}
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['frequency']))
                            <span class="badge bg-{{ $collection['frequency_badge'] ?? 'secondary' }}">
                                {{ $collection['frequency'] }}
                            </span>
                            @if(strtolower($collection['frequency']) === 'fortnightly')
                                <br><small class="text-muted">
                                    @if(isset($collection['should_deliver_this_week']))
                                        {{ $collection['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                    @endif
                                </small>
                            @endif
                        @else
                            <span class="badge bg-{{ isset($collection['subscription_id']) ? 'success' : 'info' }}">
                                {{ isset($collection['subscription_id']) ? 'Weekly Collection' : 'One-time' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['customer_week_type']) && $collection['customer_week_type'] !== 'Weekly')
                            <div class="dropdown">
                                <button class="btn btn-sm badge bg-{{ $collection['week_badge'] ?? 'secondary' }} dropdown-toggle" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                        style="border: none;">
                                    Week {{ $collection['customer_week_type'] }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item week-change-btn" 
                                           href="#" 
                                           data-customer-id="{{ $collection['id'] }}"
                                           data-current-week="{{ $collection['customer_week_type'] }}"
                                           data-new-week="A">
                                        <span class="badge bg-success me-2">A</span>Week A (Odd weeks)
                                    </a></li>
                                    <li><a class="dropdown-item week-change-btn" 
                                           href="#" 
                                           data-customer-id="{{ $collection['id'] }}"
                                           data-current-week="{{ $collection['customer_week_type'] }}"
                                           data-new-week="B">
                                        <span class="badge bg-info me-2">B</span>Week B (Even weeks)
                                    </a></li>
                                </ul>
                            </div>
                            <br><small class="text-muted">
                                Current: Week {{ $collection['current_week_type'] ?? '?' }}
                                @if(isset($collection['should_deliver_this_week']))
                                    | {{ $collection['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                @endif
                            </small>
                        @else
                            <span class="badge bg-primary">Every Week</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ isset($collection['status']) && $collection['status'] === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($collection['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        @if(isset($collection['next_payment']))
                            @if(is_numeric($collection['next_payment']) && $collection['next_payment'] == 0)
                                <span class="text-warning">Pending</span>
                            @else
                                <small>{{ date('Y-m-d', strtotime($collection['next_payment'])) }}</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['customer_email']))
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                        title="Switch to this user's account">
                                    <i class="fas fa-user-circle"></i> Switch to User
                                </button>
                                <button class="btn btn-sm btn-outline-success subscription-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                        title="View subscription in WooCommerce">
                                    <i class="fas fa-shopping-cart"></i> View Subscription
                                </button>
                                <button class="btn btn-sm btn-outline-info profile-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                        title="View user profile in WordPress">
                                    <i class="fas fa-user"></i> View Profile
                                </button>
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
