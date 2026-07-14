const uploadErrorMessage = (event, visible) => {
    const container = event.target.closest('[data-upload-container]');
    const message = container?.querySelector('[data-upload-error]');

    if (message) {
        message.hidden = !visible;
    }
};

document.addEventListener('livewire-upload-start', (event) => uploadErrorMessage(event, false), true);
document.addEventListener('livewire-upload-finish', (event) => uploadErrorMessage(event, false), true);
document.addEventListener('livewire-upload-error', (event) => uploadErrorMessage(event, true), true);
