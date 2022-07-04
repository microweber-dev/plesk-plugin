import {
    Alert,
    Component,
    createElement,
    PropTypes,
} from '@plesk/plesk-ext-sdk';

import axios from 'axios';

export default class Overview extends Component {
    static propTypes = {
        baseUrl: PropTypes.string.isRequired,
    };

    state = {
        time: null,
    };

    componentDidMount() {
        const { baseUrl } = this.props;
        axios.get(`${baseUrl}/api/ping`).then(({ data }) => this.setState({ time: data }));
    }

    render() {
        const { time } = this.state;

        if (!time) {
            return null;
        }

        return (
            <Alert intent="info">
                {`Server response: ${time}`}
            </Alert>
        );
    }
}