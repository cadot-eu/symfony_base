
export default async function fetchEnv() {
    try {
        const response = await fetch('/admin/getEnvEditorjs');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return await response.json();
    } catch (error) {
        console.error('There has been a problem with your fetch operation:', error);
    }
}

