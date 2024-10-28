

<div>
    <style>
        .fi-modal-content{
            overflow: hidden;
        }
        .fi-modal-content > div {
            overflow: hidden;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: 100%; /* Ajusta la altura máxima según tu diseño */
            overflow-y: hidden; /* Habilita el desplazamiento */
            padding: 10px;
            min-height: 100%;
        }

        .messages-box {
            flex: 1;
            overflow-y: scroll;
            padding: 10px;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            max-width: fit-content;
            word-wrap: break-word;
            display: flex;
            align-items: flex-start; /* Permitir que las palabras largas se dividan */
        }

        .message .timestamp{
            margin-left: 20px;
        }

        .sent {
            background-color: rgb(60, 131, 247); /* Color de fondo para mensajes enviados */
            color: white;
            align-self: flex-end; /* Alinear a la derecha */
            margin-left: auto;
        }

        .received {
            background-color: #e5e5ea; /* Color de fondo para mensajes recibidos */
            color: black;
            align-self: flex-start; /* Alinear a la izquierda */
        }

        .timestamp {
            display: block;
            font-size: 0.75em; /* Tamaño del texto para la marca de tiempo */
            color: black;
        }

        .sent .timestamp{
            color: white;
        }

        .input-box {
            display: flex;
        }
        .fi-modal-content > div{
            height: 100%;
        }
        #messageInput{
            padding-left: 10px;
        }
    </style>



    <div class="chat-container">
        {{-- wire:poll.5s="updateMessages" --}}
        <div id="messages" class="messages-box" >
            {{-- {!! $record['body'] !!} --}}

            @foreach($messages as $key => $value)
                <div class="py-4" style="border-bottom: 1px solid gray">
                    <div style="font-weight:bold" class="mb-4">
                        {{ $value['date'] }}
                    </div>
                    <div class="p-4">
                        {!! $value['body'] !!}

                    </div>
                </div>    
            @endforeach

        </div> 

        <div class="flex w-full ">
            <div class="input-bo w-full fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                <div class="min-w-0 flex-1">
                    <input 
                        type="text" 
                        id="messageInput" 
                        class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-0 pe-3" 
                        wire:model.defer="newMessage"  
                        wire:keydown.enter.prevent="sendMessage"
                        placeholder="Type your message..."
                        wire:loading.attr="disabled"
                    >
                </div>    
            </div>
            <div>
                <button wire:loading.attr="disabled" type="button" wire:click="sendMessage" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action">Send</button>
                {{-- {{ $action->getModalAction('send') }} --}}
            </div>
            </div>
    </div>

    @script
        <script>
            $wire.on('messages-mail-created', () => {
                const messagesBox = document.getElementById('messages');
                messagesBox.scrollTop = messagesBox.scrollHeight;
            });

            $wire.on('messages-mail-updated', () => {
                setTimeout(() => {
                    const messagesBox = document.getElementById('messages');
                    messagesBox.scrollTop = messagesBox.scrollHeight;
                }, 100);
            });
        </script>
        @endscript

    {{-- <script>
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                const messagesBox = document.getElementById('messages');
                messagesBox.scrollTop = messagesBox.scrollHeight;
            });
            Livewire.on('messagesUpdated', () => {
                const messagesBox = document.getElementById('messages');
                messagesBox.scrollTop = messagesBox.scrollHeight;
            });
        });
    </script> --}}

</div>
