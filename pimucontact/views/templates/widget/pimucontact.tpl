<div class="widget-pimucontact">
    
    {if isset($pimuMessageStatus)}        
        {if $pimuMessageStatus == 'success'}
            <div class="pimucontact-message pimu-success">
                {l s='Dziękujemy za wysłanie wiadomości!' d='Shop.Forms.Pimucontact'}
            </div>
        {else}
            <div class="pimucontact-message pimu-fail">
                {if $pimuMessageStatus == 'fail'}
                    <div class="pimucontact-fail-header">
                        {l s='Wystąpiły błędy podczas wysyłania wiadomości!' d='Shop.Forms.Pimucontact'}
                    </div>
                {/if}
                <ul class="pimucontact-fail-list">
                    {foreach from=$pimuMessages item=message}
                        <li class="pimucontact-fail-message">{$message}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}
    {/if}

    <div class="pimucontact-header">
        <h6 class="pimucontact-header-title">{l s='Towar jest dostępny jedynie na zamówienie.' d='Shop.Forms.Pimucontact'}</h6>
        <p>{l s='Prosimy wypełnić poniższy formularz w przypadku zainteresowania tym przedmiotem. Odpowiadamy maksymalnie w przeciągu 24 godzin.' d="Shop.Forms.Pimucontact"}</p>
    </div>
    
    <form id="pimucontact-form" class="pimucontact-form" method="POST">
        <script>
            function submitPimuForm() {
                document.getElementById("pimucontact-form").submit();
            }
        </script>    
        <input type="hidden" name="isPimuSubmitted" value="yes">
        <input name="pimu-message-email" placeholder="{l s='Twój e-mail' d='Shop.Forms.Pimucontact'}" type="email" value="{if isset($pimuRecoverEmail)}{$pimuRecoverEmail}{/if}">
        <textarea name="pimu-message-content" placeholder="{l s='Opcjonalna wiadomość' d='Shop.Forms.Pimucontact'}"></textarea>
        {* <div class="g-recaptcha" data-sitekey="{$pimuRecaptchaSiteKey}"></div> *}
        <div class="g-recaptcha" data-sitekey="{$PIMUCONTACT_RECAPTCHA_SITEKEY}" data-bind="submitPimuMessage" data-callback="submitPimuForm"></div>
        <input id="submitPimuMessage" class="pimu-contact-form-submit" type="submit" value="{l s='Wyślij wiadomość' d='Shop.Forms.Pimucontact'}">
    </form>

</div>