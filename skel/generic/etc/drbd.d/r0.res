resource r0 {
    protocol C;
    startup {
            degr-wfc-timeout 60;
    }
    disk {
    }
    syncer {
            rate 100M;
    }
    net {
            cram-hmac-alg sha1;
            shared-secret "NODESHAREDSECRED";
    }
    on NODE01HOSTNAMEINT {
            device /dev/drbd0;
            disk NODE01SHAREDDISKDEVICE;
            address NODE01IPADDRINT:7789;
            meta-disk internal;
    }
    on NODE02HOSTNAMEINT {
            device /dev/drbd0;
            disk NODE02SHAREDDISKDEVICE;
            address NODE02IPADDRINT:7789;
            meta-disk internal;
    }
}

