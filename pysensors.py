import serial
import asyncio
import websockets

# Update your port and baud rate
ser = serial.Serial('COM13', 9600)

connected_clients = set()

async def send_serial_data(websocket):
    connected_clients.add(websocket)
    try:
        while True:
            line = ser.readline().decode().strip()
            await websocket.send(line)
            await asyncio.sleep(0.01) #0.01
    except websockets.exceptions.ConnectionClosed:
        connected_clients.remove(websocket)

async def main():
    async with websockets.serve(send_serial_data, "127.0.0.1", 8000):
        print("WebSocket server started at ws://127.0.0.1:8000")
        await asyncio.Future()  # run forever

if __name__ == "__main__":
    asyncio.run(main())
