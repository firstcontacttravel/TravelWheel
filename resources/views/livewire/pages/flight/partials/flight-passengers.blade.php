{{-- resources/views/livewire/partials/flight-passengers.blade.php --}}

<div class="input-box">
    <label class="label-text">Passengers</label>
    <div class="form-group">
        <div class="dropdown dropdown-contain">
            <i class="la la-user form-icon"></i>
            <a class="dropdown-toggle dropdown-btn travellers" href="#" role="button"
                data-toggle="dropdown" aria-expanded="false">
                <p style="font-size:12px;">
                    Travellers
                    <span class="guest_flights">
                        {{ $adults + $childs + $kids }}
                    </span>
                </p>
            </a>

            <div class="dropdown-menu dropdown-menu-wrap">

                {{-- Adults --}}
                <div class="dropdown-item adult_qty">
                    <div class="qty-box d-flex align-items-center justify-content-between"
                        style="margin-bottom:10px; border-bottom:1px solid #dedede; padding-bottom:10px;">
                        <label style="line-height:16px">
                            <i class="la la-user"></i> Adults
                            <div class="clear"></div>
                            <small style="font-size:10px">+12</small>
                        </label>
                        <div class="qtyBtn d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="decrementPassenger('adults')">−</button>
                            <span class="qtyInput_flights px-2">{{ $adults }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="incrementPassenger('adults')">+</button>
                            <input type="hidden" name="adults" wire:model="adults">
                        </div>
                    </div>
                </div>

                {{-- Children --}}
                <div class="dropdown-item child_qty">
                    <div class="qty-box d-flex align-items-center justify-content-between"
                        style="margin-bottom:10px; border-bottom:1px solid #dedede; padding-bottom:10px;">
                        <label style="line-height:16px">
                            <i class="la la-female"></i> Childs
                            <div class="clear"></div>
                            <small style="font-size:10px">2 – 11</small>
                        </label>
                        <div class="qtyBtn d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="decrementPassenger('childs')">−</button>
                            <span class="qtyInput_flights px-2">{{ $childs }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="incrementPassenger('childs')">+</button>
                            <input type="hidden" name="childs" wire:model="childs">
                        </div>
                    </div>
                </div>

                {{-- Infants --}}
                <div class="dropdown-item infant_qty">
                    <div class="qty-box d-flex align-items-center justify-content-between">
                        <label style="line-height:16px">
                            <i class="la la-female"></i> Infants
                            <div class="clear"></div>
                            <small style="font-size:10px">under 2</small>
                        </label>
                        <div class="qtyBtn d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="decrementPassenger('kids')">−</button>
                            <span class="qtyInput_flights px-2">{{ $kids }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="incrementPassenger('kids')">+</button>
                            <input type="hidden" name="kids" wire:model="kids">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>