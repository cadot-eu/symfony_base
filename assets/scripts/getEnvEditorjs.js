
export default async function fetchEnv() {
    try {
        const response = await fetch('/admin/getEnvEditorjs');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        if (typeof data !== 'object' || data === null) {
            throw new Error('Invalid JSON response');
        }
        return data;
    } catch (error) {
        console.error('There has been a problem with your fetch operation:', error);
    }
}

