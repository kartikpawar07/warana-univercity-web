const totalSteps = 9;

function nextStep(currentStep) {
    if(currentStep >= totalSteps) return;
    
    if(currentStep + 1 === 9) {
        populateSummary();
    }
    
    document.getElementById('step-' + currentStep).classList.remove('active');
    document.getElementById('step-' + (currentStep + 1)).classList.add('active');
    
    updateStepper(currentStep + 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(currentStep) {
    if(currentStep <= 1) return;
    
    document.getElementById('step-' + currentStep).classList.remove('active');
    document.getElementById('step-' + (currentStep - 1)).classList.add('active');
    
    updateStepper(currentStep - 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateStepper(currentStep) {
    const progressLine = document.getElementById('stepper-progress');
    const percentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
    progressLine.style.width = percentage + '%';

    const stepperItems = document.querySelectorAll('.stepper-item');
    stepperItems.forEach((item, index) => {
        let stepNum = index + 1;
        item.classList.remove('active', 'completed');
        if (stepNum < currentStep) {
            item.classList.add('completed');
        } else if (stepNum === currentStep) {
            item.classList.add('active');
        }
    });
}

function populateSummary() {
    const summaryContainer = document.getElementById('summary-container');
    summaryContainer.innerHTML = '';
    
    const sectionsToSummarize = [
        { id: 'step-1', title: 'Personal Information' },
        { id: 'step-2', title: 'Basic Details' },
        { id: 'step-3', title: 'Reservation & Special Category' },
        { id: 'step-4', title: 'Identification & Location' },
        { id: 'step-5', title: 'Academic Details' },
        { id: 'step-6', title: 'Address Details' }
    ];

    sectionsToSummarize.forEach(sec => {
        let section = document.getElementById(sec.id);
        if(!section) return;

        let html = `<div class="summary-section-title">${sec.title}</div>`;
        let groups = section.querySelectorAll('.form-group');
        let hasData = false;
        
        groups.forEach(group => {
            let label = group.querySelector('.form-label');
            let input = group.querySelector('.form-control, .form-select');
            
            if(label && input) {
                let labelText = label.innerText.replace('*','').trim();
                let val = input.value.trim() || '-';
                if(input.tagName === 'SELECT' && input.selectedIndex >= 0) {
                    val = input.options[input.selectedIndex].text;
                    if(val.includes('Select')) val = '-';
                }
                
                html += `<div class="summary-item">
                            <span class="summary-label">${labelText}:</span>
                            <span class="summary-value"><strong>${val}</strong></span>
                         </div>`;
                hasData = true;
            }
        });
        
        if(hasData) summaryContainer.innerHTML += html;
    });
}
