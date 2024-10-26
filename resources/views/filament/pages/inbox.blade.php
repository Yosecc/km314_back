<x-filament-panels::page>
    <div class="md:flex">
        <ul class="flex-column space-y space-y-4 text-sm font-medium text-gray-500 dark:text-gray-400 md:me-4 mb-4 md:mb-0">
            
            <li>
                <a href="#"  wire:click="$set('activeTab', 'tablaMail')"  class="@if($activeTab === 'tablaMail') hover:text-gray-900 bg-gray-50 hover:bg-gray-100  dark:bg-gray-800 dark:hover:bg-gray-700 @endif inline-flex items-center px-4 py-3 rounded-lg  w-full dark:hover:text-white">
                    <svg style="margin-right: 0.5rem" class="fi-ta-text-item-icon h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                        <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z"></path>
                        <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z"></path>
                      </svg>
                    Mail
                </a>
            </li>
            <li>
                <a href="#" wire:click="$set('activeTab', 'tablaFacebook')" class="@if($activeTab === 'tablaFacebook') hover:text-gray-900 bg-gray-50 hover:bg-gray-100  dark:bg-gray-800 dark:hover:bg-gray-700 @endif inline-flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg active w-full dark:bg-blue-600" aria-current="page">
                    <svg class="w-6 h-6 text-gray-500 dark:text-gray-400"  fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="-5.0 -10.0 110.0 135.0">
                        <path d="m66 28h8c1.1016 0 2-0.89844 2-2v-16c0-1.1016-0.89844-2-2-2h-12c-12.129 0-22 9.8711-22 22v10.16h-14c-1.1016 0-2 0.89844-2 2v15.84c0 1.1016 0.89844 2 2 2h12.609v30c0 1.1016 0.89844 2 2 2h16.699c1.1016 0 2-0.89844 2-2v-30h10.52c0.91016 0 1.7109-0.60938 1.9414-1.5l4.1719-16c0.16016-0.60156 0.03125-1.2383-0.35156-1.7305-0.37891-0.48828-0.96094-0.78125-1.5781-0.78125h-14v-6c0-3.3086 2.6914-6 6-6zm-8 16h13.41l-3.1289 12h-10.98c-1.1016 0-2 0.89844-2 2v30h-12.699v-30c0-1.1016-0.89844-2-2-2h-12.609v-11.84h14c1.1016 0 2-0.89844 2-2v-12.16c0-9.9219 8.0703-18 18-18h10v12h-6c-5.5117 0-10 4.4883-10 10v8c0 1.1016 0.89844 2 2 2z" />
                    </svg>
                    Facebook
                </a>
            </li>
            
        </ul>
        <div class="ps-4 w-full">
            <div class="p-6 bg-gray-50 text-medium w-100 text-gray-500 dark:text-gray-400 dark:bg-gray-800 rounded-lg w-full">
                {{ $this->getTable()->render() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
