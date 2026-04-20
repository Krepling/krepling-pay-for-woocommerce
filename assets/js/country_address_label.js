function updateLabelText(countryCode, addressType, id = '') {
    const labelTexts = {
        'US': ['Zip Code', 'State', 'City'],
        'CA': ['Postal Code', 'Province', 'City'],
        'GB': ['Postcode', 'Country', 'City/Town'],
        'AU': ['Postcode', 'State/Territory', 'City/Suburb'],
        'DE': ['Postleitzahl(PLZ)', 'Bundesland', 'Stadt'],
        'FR': ['Code Postal', 'Departement', 'Ville'],
        'IN': ['Pin Code', 'State', 'City/Town'],
        'JP': ['Postal Code', 'Prefecture', 'City/Town'],
        'CN': ['Postal Code', 'Province', 'City'],
        'RU': ['Postal Code', 'Oblast/Krai/Republic', 'City/Town'],
        'BR': ['Codigo De Endereçamento Postal(CEP)', 'State', 'City'],
        'IT': ['Codice Di Avviamento Postale(CAP)', 'Region', 'City'],
        'MX': ['Codigo Postal', 'Estado', 'Ciudad'],
        'ES': ['Codigo Postal', 'Provincia', 'Ciudad'],
        'ZA': ['Postal Code', 'Province', 'City/Town'],
        'NL': ['Postcode', 'Province', 'Stad'],
        'AR': ['Codigo Postal', 'Provincia', 'Ciudad'],
        'SA': ['Postal Code', 'Region', 'City'],
        'TR': ['Posta Kodu', 'Province', 'District'],
        'NG': ['Postal Code', 'State', 'City/Town'],
        'KR': ['Postal Code', 'Province', 'City']
    };
    const defaultLabels = ['Zip Code', 'State', 'City'];
    const labelText = labelTexts[countryCode] || defaultLabels;    

    if(id == ''){
        if (addressType == 'shipping') {
            $('label[for="registerZip"]').text(labelText[0]); 
            $('label[for="registerState"]').text(labelText[1]); 
            $('label[for="registerCity"]').text(labelText[2]);
        } else {
            $('label[for="billingZip"]').text(labelText[0]); 
            $('label[for="billingState"]').text(labelText[1]); 
            $('label[for="billingCity"]').text(labelText[2]);
        }
    } else {
        if (addressType == 'shipping') {
            $('label[for="uzip_'+id+'"]').text(labelText[0]); 
            $('label[for="update_stateDropdown_'+id+'"]').text(labelText[1]); 
            $('label[for="ucity_'+id+'"]').text(labelText[2]);
        } else {
            $('label[for="update_billingZip_'+id+'"]').text(labelText[0]); 
            $('label[for="update_billingStateDropdown_'+id+'"]').text(labelText[1]); 
            $('label[for="update_billingCity_'+id+'"]').text(labelText[2]);
        }
    }
    
}