{{-- Collection Table Partial --}}
<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Address</th>
                <th>Products</th>
                <th>Contact</th>
                <th>Frequency</th>
                <th>Week</th>
                <th>Status</th>
                <th>Next Payment</th>
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
                        @if(isset($collection['address']) && is_array($collection['address']))
                            @foreach($collection['address'] as $line)
                                @if(!empty($line))
                                    {{ $line }}<br>
                                @endif
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['products']) && is_array($collection['products']))
                            @foreach($collection['products'] as $product)
                                <div class="mb-1">
                                    <strong>{{ $product['name'] ?? 'Product' }}</strong>
                                    @if(isset($product['quantity']))
                                        <span class="badge bg-secondary">{{ $product['quantity'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['phone']) && !empty($collection['phone']))
                            <i class="fas fa-phone"></i> {{ $collection['phone'] }}<br>
                        @endif
                        @if(isset($collection['email']) && !empty($collection['email']))
                            <i class="fas fa-envelope"></i> {{ $collection['email'] }}
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $collection['frequency_badge'] ?? 'secondary' }}">
                            {{ $collection['frequency'] ?? 'Weekly' }}
                        </span>
                    </td>
                    <td>
                        @if(isset($collection['frequency']) && strtolower($collection['frequency']) === 'fortnightly')
                            <span class="badge bg-{{ $collection['week_badge'] ?? 'secondary' }}">
                                Week {{ $collection['week_type'] ?? 'A' }}
                            </span>
                            @if(isset($collection['should_deliver']) && !$collection['should_deliver'])
                                <br><small class="text-muted">Skip week</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
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
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
