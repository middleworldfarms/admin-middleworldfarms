{{-- Collection Table Partial --}}
<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Address</th>
                <th>Products/Notes</th>
                <th>Contact</th>
                <th>Collection Day</th>
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
                    <td>
                        <strong>{{ $collection['name'] ?? 'N/A' }}</strong>
                        @if(isset($collection['id']))
                            <br><small class="text-muted">ID: {{ $collection['id'] }}</small>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['address']) && is_array($collection['address']) && !empty(array_filter($collection['address'])))
                            @foreach($collection['address'] as $addressLine)
                                @if(!empty($addressLine))
                                    {{ $addressLine }}<br>
                                @endif
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['products']) && is_array($collection['products']))
                            @foreach($collection['products'] as $product)
                                {{ $product['name'] ?? 'Product' }} ({{ $product['quantity'] ?? 1 }})<br>
                            @endforeach
                        @else
                            <small class="text-muted">No product details</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['phone']))
                            <i class="fas fa-phone"></i> {{ $collection['phone'] }}<br>
                        @endif
                        @if(!empty($collection['email']))
                            <i class="fas fa-envelope"></i> {{ $collection['email'] }}
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['preferred_collection_day']))
                            <span class="badge bg-info">
                                {{ $collection['preferred_collection_day'] }}
                            </span>
                            <br>
                            <small class="text-muted">Customer's preference</small>
                        @else
                            <span class="badge bg-secondary">Not Set</span>
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
                            <span class="badge bg-success">
                                Weekly Collection
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
                            <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                    data-email="{{ $collection['customer_email'] }}" 
                                    data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                    title="Switch to this user's account">
                                <i class="fas fa-user-circle"></i> Switch to User
                            </button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
