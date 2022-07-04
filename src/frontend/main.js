import { render, createElement } from '@plesk/ui-library';
import App from './components/App';

module.exports = (container, props) => {
    render(
        <App {...props} />,
        container,
    );
};